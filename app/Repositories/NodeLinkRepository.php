<?php

namespace App\Repositories;

use App\Constants\ParsingConstants;
use App\Models\NodeLink;
use App\Models\Source;
use Illuminate\Support\Collection;

/**
 * Class NodeLinkRepository
 *
 * Repository for managing NodeLink model operations.
 * Centralizes all database interactions related to article links.
 * Provides clean, business-level methods instead of raw Eloquent queries.
 *
 * Usage:
 *   $repository = new NodeLinkRepository();
 *   $repository->saveNewLinks($urls, $source);
 *   $repository->markAsProcessed($link);
 */
class NodeLinkRepository
{
    /**
     * NodeLink model instance
     */
    protected NodeLink $model;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->model = new NodeLink;
    }

    /**
     * Get unprocessed links with retry limit applied
     *
     * Excludes links that have exceeded maximum retry attempts.
     *
     * @return Collection Collection of unparsed NodeLink records within retry limit
     */
    public function getUnprocessedWithinRetryLimit(): Collection
    {
        return NodeLink::where('parsed', ParsingConstants::STATUS_UNPARSED)
            ->where('attempts', '<', ParsingConstants::MAX_RETRY_ATTEMPTS)
            ->get();
    }

    /**
     * Get a link by URL
     *
     * @param  string  $url  Article URL
     */
    public function getByUrl(string $url): ?NodeLink
    {
        return NodeLink::where('url', $url)->first();
    }

    /**
     * Check if URL already exists
     *
     * @param  string  $url  Article URL
     * @return bool True if URL exists, false otherwise
     */
    public function urlExists(string $url): bool
    {
        return NodeLink::where('url', $url)->exists();
    }

    /**
     * Get existing URLs from a collection
     *
     * Useful for batch processing to avoid duplicates.
     *
     * @param  Collection  $urls  Collection of URLs to check
     * @return Collection Collection of existing URLs
     */
    public function getExistingUrls(Collection $urls): Collection
    {
        if ($urls->isEmpty()) {
            return collect([]);
        }

        return NodeLink::whereIn('url', $urls->toArray())
            ->pluck('url');
    }

    /**
     * Get new (non-existing) URLs from a collection
     *
     * Opposite of getExistingUrls - returns URLs that DON'T exist in DB.
     *
     * @param  Collection  $urls  Collection of URLs to check
     * @return Collection Collection of new URLs
     */
    public function getNewUrls(Collection $urls): Collection
    {
        if ($urls->isEmpty()) {
            return collect([]);
        }

        $existingUrls = $this->getExistingUrls($urls);

        return $urls->filter(fn ($url) => ! $existingUrls->contains($url));
    }

    /**
     * Save new links to database
     *
     * Creates new NodeLink records for URLs that don't already exist.
     * Returns count of successfully saved links.
     *
     * @param  Collection  $urls  Collection of URLs to save
     * @param  Source  $source  Source record to associate with links
     * @param  string  $type  Link type (html or rss)
     * @return int Number of links saved
     */
    public function saveNewLinks(Collection $urls, Source $source, string $type = ParsingConstants::TYPE_HTML): int
    {
        if ($urls->isEmpty()) {
            return 0;
        }

        $newUrls = $this->getNewUrls($urls);

        if ($newUrls->isEmpty()) {
            return 0;
        }

        $linkCount = 0;

        foreach ($newUrls as $url) {
            NodeLink::create([
                'url' => $url,
                'source' => $source->url,
                'type' => $type,
                'parsed' => ParsingConstants::STATUS_UNPARSED,
                'attempts' => 0,
                'use_browser' => $source->need_browser ?? 0,
            ]);
            $linkCount++;
        }

        return $linkCount;
    }

    /**
     * Mark link as processed (parsed)
     *
     * Updates the parsed status to indicate successful processing.
     * Clears the last_error field.
     *
     * @param  NodeLink  $link  Link to mark as processed
     * @return bool Success status
     */
    public function markAsProcessed(NodeLink $link): bool
    {
        return $link->update([
            'parsed' => ParsingConstants::STATUS_PARSED,
            'last_error' => null,
        ]);
    }

    /**
     * Mark link as duplicate
     *
     * Updates link status to indicate it's a duplicate of another article.
     * Associates it with the original Node ID.
     *
     * @param  NodeLink  $link  Link to mark as duplicate
     * @param  int|null  $originalNodeId  Node ID of the original article (if available)
     * @return bool Success status
     */
    public function markAsDuplicate(NodeLink $link, ?int $originalNodeId = null): bool
    {
        return $link->update([
            'parsed' => ParsingConstants::STATUS_PARSED,
            'is_duplicate' => true,
            'duplicate_of' => $originalNodeId,
            'last_error' => null,
        ]);
    }

    /**
     * Increment retry attempts counter
     *
     * Increases the attempts count by 1. Used when processing fails.
     *
     * @param  NodeLink  $link  Link to increment attempts for
     * @return bool Success status
     */
    public function incrementAttempts(NodeLink $link): bool
    {
        return $link->increment('attempts');
    }

    /**
     * Record error for a link
     *
     * Stores error message and increments attempts counter.
     * Called when processing encounters an exception.
     *
     * @param  NodeLink  $link  Link associated with error
     * @param  string  $errorMessage  Error description
     * @param  string  $errorType  Error type from ParsingConstants
     * @return bool Success status
     */
    public function recordError(
        NodeLink $link,
        string $errorMessage,
        string $errorType = ParsingConstants::ERROR_PARSING
    ): bool {
        $this->incrementAttempts($link);

        return $link->update([
            'last_error' => $errorMessage,
            'error_type' => $errorType,
        ]);
    }

    /**
     * Get links with errors
     *
     * Returns all links that have encountered errors during processing.
     *
     * @param  int|null  $limit  Optional: limit results
     * @return Collection Collection of links with errors
     */
    public function getWithErrors(?int $limit = null): Collection
    {
        $query = NodeLink::whereNotNull('last_error');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get duplicate links
     *
     * Returns all links marked as duplicates.
     *
     * @param  int|null  $limit  Optional: limit results
     * @return Collection Collection of duplicate links
     */
    public function getDuplicates(?int $limit = null): Collection
    {
        $query = NodeLink::where('is_duplicate', true);

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get links by source
     *
     * Returns all links associated with a specific source.
     *
     * @param  Source  $source  Source to get links for
     * @param  bool  $processedOnly  If true, only return processed links
     * @return Collection Collection of links
     */
    public function getBySource(Source $source, bool $processedOnly = false): Collection
    {
        $query = NodeLink::where('source_id', $source->id);

        if ($processedOnly) {
            $query->where('parsed', ParsingConstants::STATUS_PARSED);
        }

        return $query->get();
    }

    /**
     * Get links by type
     *
     * Returns all links of a specific type (html or rss).
     *
     * @param  string  $type  Link type (html or rss)
     * @return Collection Collection of links
     */
    public function getByType(string $type): Collection
    {
        return NodeLink::where('type', $type)
            ->get();
    }

    /**
     * Clear errors for a link
     *
     * Resets error-related fields to retry processing.
     *
     * @param  NodeLink  $link  Link to clear errors for
     * @return bool Success status
     */
    public function clearErrors(NodeLink $link): bool
    {
        return $link->update([
            'last_error' => null,
            'error_type' => null,
        ]);
    }

    /**
     * Reset retry attempts
     *
     * Resets the attempts counter to 0 (useful for retrying failed links).
     *
     * @param  NodeLink  $link  Link to reset attempts for
     * @return bool Success status
     */
    public function resetAttempts(NodeLink $link): bool
    {
        return $link->update([
            'attempts' => 0,
        ]);
    }

    /**
     * Get statistics about links
     *
     * Returns an array of statistics about all links in the database.
     *
     * @return array Array with statistics
     */
    public function getStatistics(): array
    {
        $total = NodeLink::count();
        $processed = NodeLink::where('parsed', ParsingConstants::STATUS_PARSED)->count();
        $unprocessed = NodeLink::where('parsed', ParsingConstants::STATUS_UNPARSED)->count();
        $duplicates = NodeLink::where('is_duplicate', true)->count();
        $withErrors = NodeLink::whereNotNull('last_error')->count();

        return [
            'total' => $total,
            'processed' => $processed,
            'unprocessed' => $unprocessed,
            'duplicates' => $duplicates,
            'with_errors' => $withErrors,
            'success_rate' => $total > 0 ? ($processed / $total) * 100 : 0,
        ];
    }

    /**
     * Delete old links
     *
     * Useful for cleanup - removes links older than specified days.
     *
     * @param  int  $daysOld  Number of days
     * @return int Number of deleted records
     */
    public function deleteOlderThan(int $daysOld): int
    {
        $date = now()->subDays($daysOld);

        return NodeLink::where('created_at', '<', $date)
            ->where('parsed', ParsingConstants::STATUS_PARSED)
            ->delete();
    }

    /**
     * Batch update status
     *
     * Updates status for multiple links at once.
     *
     * @param  Collection  $links  Collection of NodeLink records
     * @param  int  $status  New status value
     * @return int Number of updated records
     */
    public function batchUpdateStatus(Collection $links, int $status): int
    {
        if ($links->isEmpty()) {
            return 0;
        }

        $ids = $links->pluck('id')->toArray();

        return NodeLink::whereIn('id', $ids)
            ->update(['parsed' => $status]);
    }
}
