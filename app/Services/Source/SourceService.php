<?php

namespace App\Services\Source;

use App\Models\Source;
use App\Services\Logs\LoggerService;
use Illuminate\Support\Facades\Http;

/**
 * Service for managing content sources.
 *
 * Handles detection of RSS feeds, determining if full content is available,
 * adding, deleting, and rotating active sources.
 */
class SourceService
{
    protected LoggerService $logger;

    /**
     * RSS markers that indicate full content availability.
     */
    protected const FULL_CONTENT_MARKERS = [
        '<content:encoded>',
        '<full-text>',
    ];

    /**
     * SourceService constructor.
     */
    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Add a new source.
     *
     * Detects RSS URL, checks if full RSS content is available,
     * determines if a browser is required, and saves the source.
     */
    public function addSource(string $url): void
    {
        $activeExists = Source::where('isActive', 1)->exists();

        $rssUrl = null;
        $needBrowser = false;
        $fullRssContent = false;

        try {
            // First request via Puppeteer for full page load
            $response = Http::timeout(30)->get(env('PUPPETEER_URL', 'http://puppeteer:3000') . '/scrape', [
               'source' => $url,
            ]);

            if (! $response->successful()) {
                throw new \Exception('Puppeteer scrape failed');
            }

            $html = $response->body();

            // Determine if a browser is required
            $headResponse = Http::timeout(5)->get($url);
            if ($headResponse->failed() || $headResponse->status() === 403) {
                $needBrowser = true;
            }

            // Detect RSS URL
            $rssUrl = $this->detectRssUrl($html, $url);

            // Check for full RSS content if RSS found
            if ($rssUrl) {
                try {
                    $rssResponse = Http::timeout(10)->get($rssUrl);
                    if ($rssResponse->successful()) {
                        $rssXml = $rssResponse->body();
                        foreach (self::FULL_CONTENT_MARKERS as $marker) {
                            if (str_contains($rssXml, $marker)) {
                                $fullRssContent = true;
                                break;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->log('SourceService', 'WARNING', 'RSS fetch failed', [
                        'rss_url' => $rssUrl,
                        'exception' => $e->getMessage(),
                    ]);
                }
            }

            $this->logger->log('SourceService', 'INFO', 'RSS detected', [
                'url' => $url,
                'rss_url' => $rssUrl,
                'full_rss_content' => $fullRssContent,
                'need_browser' => $needBrowser,
            ]);

        } catch (\Exception $e) {
            $this->logger->log('SourceService', 'ERROR', 'RSS detection failed', [
                'url' => $url,
                'exception' => $e->getMessage(),
            ]);
        }

        // Save the source
        Source::create([
            'url' => $url,
            'rss_url' => $rssUrl,
            'need_browser' => $needBrowser,
            'full_rss_content' => $fullRssContent,
            'isActive' => $activeExists ? 0 : 1,
        ]);
    }

    /**
     * Detect RSS URL from HTML content.
     */
    protected function detectRssUrl(string $html, string $baseUrl): ?string
    {
        $patterns = [
            '/<link[^>]+rel=["\']alternate["\'][^>]+type=["\']application\/rss\+xml["\'][^>]+href=["\']([^"\']+)["\']/is',
            '/<a[^>]+href=["\']([^"\']*rss[^"\']*)["\']/is',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $match)) {
                $tmpUrl = $match[1];
                if (! preg_match('/utm_(source|medium|campaign|term|content)=/i', $tmpUrl)) {
                    return str_starts_with($tmpUrl, 'http')
                        ? $tmpUrl
                        : rtrim($baseUrl, '/').'/'.ltrim($tmpUrl, '/');
                }
            }
        }

        // Fallback: try HEAD /rss
        try {
            $headResponse = Http::timeout(10)->head(rtrim($baseUrl, '/').'/rss');
            if ($headResponse->successful()) {
                return (string) $headResponse->effectiveUri();
            }
        } catch (\Exception $e) {
            // Ignored, can log if desired
        }

        return null;
    }

    /**
     * Delete a source.
     *
     * If the deleted source was active, activate the next available source.
     */
    public function deleteSource(Source $source): void
    {
        $wasActive = $source->isActive;
        $source->delete();

        if ($wasActive) {
            $next = Source::orderBy('id')->first();
            if ($next && ! $next->isActive) {
                $next->update(['isActive' => 1]);
            }
        }

        $this->logger->log('SourceService', 'INFO', 'Source deleted', [
            'source_id' => $source->id,
            'url' => $source->url,
        ]);
    }

    /**
     * Rotate the active source.
     *
     * Deactivates all sources and activates the next one in order.
     *
     * @return Source|null The previously active source
     */
    public function rotateActiveSource(): ?Source
    {
        $source = Source::where('isActive', 1)->first() ?? Source::first();
        if (! $source) {
            $this->logger->log('SourceService', 'WARNING', 'No sources found to rotate.');

            return null;
        }

        // Deactivate all sources
        Source::query()->update(['isActive' => 0]);

        // Activate next source
        $next = Source::where('id', '>', $source->id)->first() ?? Source::first();
        if ($next) {
            $next->isActive = 1;
            $next->save();
        }

        $this->logger->log('SourceService', 'INFO', 'Active source rotated', [
            'previous_id' => $source->id,
            'next_id' => $next?->id,
            'url' => $source->url,
        ]);

        return $source;
    }
}
