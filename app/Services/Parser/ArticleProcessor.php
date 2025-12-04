<?php

namespace App\Services\Parser;

use App\Constants\ParsingConstants;
use App\Helpers\ChromaIdHelper;
use App\Models\Node;
use App\Models\NodeEmbedding;
use App\Models\NodeLink;
use App\Repositories\NodeLinkRepository;
use App\Services\EmbeddingService;
use App\Services\Logs\LoggerService;
use Illuminate\Support\Facades\DB;

/**
 * Class ArticleProcessor
 *
 * Core service for processing parsed articles.
 * Handles deduplication, normalization, hashing, and persistence.
 *
 * This service consolidates the business logic that was previously duplicated
 * between ConsoleParseLinks::processRssItem() and ConsoleParseArticles::handle().
 *
 * Workflow:
 * 1. Validate retry attempts limit
 * 2. Normalize article data
 * 3. Generate content hash
 * 4. Check for duplicates via EmbeddingService
 * 5. Handle duplicate or save as unique article
 * 6. Log all steps with context
 *
 * Usage:
 *   $processor = new ArticleProcessor($parser, $embeddingService, $logger, $repository);
 *   $success = $processor->processArticle($link, $parsedData, 'html');
 */
class ArticleProcessor
{
    /**
     * Parser service for extracting article content
     */
    protected ArticleContentParser $parser;

    /**
     * Embedding service for duplicate detection
     */
    protected EmbeddingService $embeddingService;

    /**
     * Logger service for structured logging
     */
    protected LoggerService $logger;

    /**
     * Repository for NodeLink database operations
     */
    protected NodeLinkRepository $repository;

    /**
     * Constructor
     */
    public function __construct(
        ArticleContentParser $parser,
        EmbeddingService $embeddingService,
        LoggerService $logger,
        ?NodeLinkRepository $repository = null
    ) {
        $this->parser = $parser;
        $this->embeddingService = $embeddingService;
        $this->logger = $logger;
        $this->repository = $repository ?? new NodeLinkRepository;
    }

    /**
     * Process a parsed article
     *
     * Main entry point for article processing.
     * Coordinates all steps: validation, normalization, deduplication, persistence.
     *
     * @param  NodeLink  $link  NodeLink record to process
     * @param  array  $parsedData  Parsed article data (from parser)
     * @param  string  $source  Article source type ('html' or 'rss')
     * @return bool Success status
     *
     * @example
     *   $parsed = $this->parser->parseHtml($url);
     *   $success = $processor->processArticle($link, $parsed, 'html');
     */
    public function processArticle(
        NodeLink $link,
        array $parsedData,
        string $source = ParsingConstants::SOURCE_HTML
    ): bool {
        // Validate: check if we've exceeded max retry attempts
        if ($link->attempts >= ParsingConstants::MAX_RETRY_ATTEMPTS) {
            $this->logger->log(
                ParsingConstants::COMMAND_PARSE_ARTICLES,
                ParsingConstants::LOG_WARNING,
                'Skipped: Too many attempts',
                [
                    'url' => $link->url,
                    'attempts' => $link->attempts,
                    'max_attempts' => ParsingConstants::MAX_RETRY_ATTEMPTS,
                ]
            );

            return false;
        }

        try {
            // Step 1: Normalize article data (decode HTML entities, etc.)
            $normalized = $this->normalizeArticleData($parsedData);

            // Step 2: Generate unique hash
            $hash = HashService::generate($normalized['title'], $normalized['content']);
            $normalized['hash'] = $hash;
            $normalized['url'] = $link->url;

            $this->logger->log(
                ParsingConstants::COMMAND_PARSE_ARTICLES,
                ParsingConstants::LOG_DEBUG,
                'Article normalized and hashed',
                [
                    'url' => $link->url,
                    'hash' => $hash,
                ]
            );

            // Step 3: Check for duplicates
            $duplicateCheck = $this->embeddingService->checkDuplicate($normalized['content']);

            $this->logger->log(
                ParsingConstants::COMMAND_PARSE_ARTICLES,
                ParsingConstants::LOG_DEBUG,
                'Duplicate check completed',
                [
                    'url' => $link->url,
                    'is_duplicate' => $duplicateCheck['duplicate'] ?? false,
                    'similarity' => $duplicateCheck['similarity'] ?? null,
                    'threshold' => $this->embeddingService->getThreshold(),
                ]
            );

            // Handle embedding service errors
            if (! empty($duplicateCheck['error']) && $duplicateCheck['error'] === true) {
                $this->logger->log(
                    ParsingConstants::COMMAND_PARSE_ARTICLES,
                    ParsingConstants::LOG_ERROR,
                    'Embedding service error',
                    [
                        'url' => $link->url,
                        'error_message' => $duplicateCheck['message'] ?? 'Unknown error',
                    ]
                );

                $this->repository->recordError(
                    $link,
                    $duplicateCheck['message'] ?? 'Embedding service error',
                    ParsingConstants::ERROR_SERVICE
                );

                return false;
            }

            // Step 4: Handle based on duplicate status
            if ($duplicateCheck['duplicate'] === true) {
                return $this->handleDuplicate($link, $duplicateCheck);
            }

            // Step 5: Save as unique article
            return $this->saveUniqueArticle($link, $normalized, $duplicateCheck, $source);

        } catch (\Exception $e) {
            // Log and record error
            $this->logger->log(
                ParsingConstants::COMMAND_PARSE_ARTICLES,
                ParsingConstants::LOG_ERROR,
                'Article processing failed',
                [
                    'url' => $link->url,
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                ]
            );

            $this->repository->recordError(
                $link,
                $e->getMessage(),
                ParsingConstants::ERROR_PARSING
            );

            return false;
        }
    }

    /**
     * Normalize article data
     *
     * Cleans up raw parsed data:
     * - Decodes HTML entities
     * - Handles null/empty values
     * - Ensures consistent format
     *
     * @param  array  $data  Raw parsed article data
     * @return array Normalized data
     */
    protected function normalizeArticleData(array $data): array
    {
        return [
            'title' => html_entity_decode(
                $data['title'] ?? '',
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            ),
            'content' => html_entity_decode(
                $data['content'] ?? '',
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            ),
            'summary' => html_entity_decode(
                $data['summary'] ?? '',
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            ),
            'image' => $data['image'] ?? null,
            'timestamp' => $data['timestamp'] ?? now(),
        ];
    }

    /**
     * Handle duplicate article
     *
     * When a duplicate is detected:
     * 1. Find original Node ID
     * 2. Mark link as duplicate
     * 3. Log the event
     *
     * @param  NodeLink  $link  NodeLink being processed
     * @param  array  $duplicateCheck  Result from EmbeddingService
     * @return bool Success status
     */
    protected function handleDuplicate(NodeLink $link, array $duplicateCheck): bool
    {
        // Extract IDs from embedding check result
        $chromaId = $duplicateCheck['chroma_id'] ?? $duplicateCheck['parent_id'] ?? null;
        $originalNodeId = ChromaIdHelper::findOriginalNodeId($chromaId);

        $this->logger->log(
            ParsingConstants::COMMAND_PARSE_ARTICLES,
            ParsingConstants::LOG_INFO,
            'Duplicate article detected',
            [
                'url' => $link->url,
                'similarity' => $duplicateCheck['similarity'] ?? null,
                'duplicate_of_node' => $originalNodeId,
                'threshold' => $this->embeddingService->getThreshold(),
            ]
        );

        // Mark link as duplicate in database
        return $this->repository->markAsDuplicate($link, $originalNodeId);
    }

    /**
     * Save unique article
     *
     * When article is unique:
     * 1. Create or retrieve Node record
     * 2. Create embedding relation
     * 3. Update link status
     * 4. All within a database transaction
     *
     * @param  NodeLink  $link  NodeLink being processed
     * @param  array  $normalized  Normalized article data
     * @param  array  $duplicateCheck  Result from EmbeddingService
     * @param  string  $source  Article source type
     * @return bool Success status
     */
    protected function saveUniqueArticle(
        NodeLink $link,
        array $normalized,
        array $duplicateCheck,
        string $source
    ): bool {
        try {
            DB::transaction(function () use ($link, $normalized, $duplicateCheck, $source) {
                // Create or get existing Node by hash
                $node = Node::firstOrCreate(
                    ['hash' => $normalized['hash']],
                    $normalized
                );

                // Mark link as processed
                $this->repository->markAsProcessed($link);

                // Extract Chroma embedding ID
                $chromaId = $duplicateCheck['parent_id'] ?? $duplicateCheck['chroma_id'] ?? null;

                // Create embedding relation if Chroma ID is available
                if (! empty($chromaId)) {
                    NodeEmbedding::firstOrCreate(
                        ['node_id' => $node->id],
                        [
                            'chroma_id' => $chromaId,
                            'similarity' => isset($duplicateCheck['similarity'])
                                ? (float) $duplicateCheck['similarity']
                                : null,
                        ]
                    );
                }

                // Log success
                $this->logger->log(
                    ParsingConstants::COMMAND_PARSE_ARTICLES,
                    ParsingConstants::LOG_INFO,
                    'Unique article saved successfully',
                    [
                        'node_id' => $node->id,
                        'url' => $link->url,
                        'source' => $source,
                        'chroma_id' => $chromaId,
                    ]
                );
            });

            return true;

        } catch (\Exception $e) {
            $this->logger->log(
                ParsingConstants::COMMAND_PARSE_ARTICLES,
                ParsingConstants::LOG_ERROR,
                'Failed to save article to database',
                [
                    'url' => $link->url,
                    'error' => $e->getMessage(),
                ]
            );

            throw $e;
        }
    }

    /**
     * Get repository instance
     *
     * Useful for accessing repository methods directly if needed.
     */
    public function getRepository(): NodeLinkRepository
    {
        return $this->repository;
    }

    /**
     * Get embedding service instance
     *
     * Useful for accessing embedding service methods directly if needed.
     */
    public function getEmbeddingService(): EmbeddingService
    {
        return $this->embeddingService;
    }

    /**
     * Process article and retry on failure
     *
     * Attempts to process article, and retries with delay on failure.
     * Useful for handling transient errors.
     *
     * @param  NodeLink  $link  NodeLink to process
     * @param  array  $parsedData  Parsed article data
     * @param  string  $source  Article source
     * @param  int  $maxRetries  Maximum number of retries
     * @param  int  $delayMs  Delay between retries in milliseconds
     * @return bool Success status
     */
    public function processArticleWithRetry(
        NodeLink $link,
        array $parsedData,
        string $source = ParsingConstants::SOURCE_HTML,
        int $maxRetries = 2,
        int $delayMs = 500
    ): bool {
        $attempt = 0;

        while ($attempt <= $maxRetries) {
            try {
                $result = $this->processArticle($link, $parsedData, $source);

                if ($result) {
                    return true;
                }

                $attempt++;

                if ($attempt <= $maxRetries) {
                    usleep($delayMs * 1000);
                }

            } catch (\Exception $e) {
                $attempt++;

                if ($attempt > $maxRetries) {
                    throw $e;
                }

                usleep($delayMs * 1000);
            }
        }

        return false;
    }
}
