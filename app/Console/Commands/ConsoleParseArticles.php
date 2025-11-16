<?php

namespace App\Console\Commands;

use App\Models\Node;
use App\Models\NodeEmbedding;
use App\Models\NodeLink;
use App\Services\EmbeddingService;
use App\Services\Logs\LoggerService;
use App\Services\Parser\ArticleContentParser;
use App\Services\Parser\HashService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Class ConsoleParseArticles
 *
 * This command performs Stage 2 parsing:
 * - Loads unprocessed links from the `nodes_links` table
 * - Skips links with too many failed attempts
 * - Parses article content using ArticleContentParser
 * - Normalizes text and generates a unique hash
 * - Checks for duplicates using EmbeddingService
 * - If duplicate: marks link as duplicate and skips
 * - If unique: saves Node, creates NodeEmbedding relation, updates link status
 * - Logs all steps with detailed contextual information
 *
 * It is intended to be used as part of the multi-stage parsing pipeline.
 */
class ConsoleParseArticles extends Command
{
    /**
     * Console command signature.
     *
     * @var string
     */
    protected $signature = 'ConsoleParseArticles';

    /**
     * Short description of the console command.
     *
     * @var string
     */
    protected $description = 'Parse articles from stored links and save them into the nodes table';

    /**
     * Service responsible for parsing article content.
     */
    protected ArticleContentParser $parser;

    /**
     * Logger service for writing structured logs.
     */
    protected LoggerService $logger;

    /**
     * Embedding service used for duplicate checking and vector storage.
     */
    protected EmbeddingService $embeddingService;

    /**
     * Constructor.
     */
    public function __construct(
        ArticleContentParser $parser,
        LoggerService $logger,
        EmbeddingService $embeddingService
    ) {
        parent::__construct();
        $this->parser = $parser;
        $this->logger = $logger;
        $this->embeddingService = $embeddingService;
    }

    /**
     * Execute the console command.
     *
     * Workflow:
     * - Load all unprocessed NodeLink entries
     * - Skip links with too many failed attempts
     * - Parse article HTML and normalize content
     * - Generate hash and check for duplicates using EmbeddingService
     * - If duplicate: mark NodeLink and skip
     * - If unique: save Node, create embedding relation, update link status
     * - Log all steps with detailed context information
     */
    public function handle(): void
    {
        $links = NodeLink::where('parsed', 0)->get();

        if ($links->isEmpty()) {
            $this->logger->log('ConsoleParseArticles', 'INFO', 'No unprocessed links found.', [
                'type' => 'console',
            ]);

            return;
        }

        $this->logger->log('ConsoleParseArticles', 'INFO', 'Found links to process', [
            'type' => 'console',
            'count' => $links->count(),
        ]);

        foreach ($links as $link) {

            // Skip links that failed 3+ times
            if ($link->attempts >= 3) {
                $this->logger->log('ConsoleParseArticles', 'WARNING', 'Skipping article (too many attempts)', [
                    'type' => 'console',
                    'url' => $link->url,
                    'attempts' => $link->attempts,
                ]);

                continue;
            }

            try {
                $this->logger->log('ConsoleParseArticles', 'INFO', 'Processing article', [
                    'url' => $link->url,
                    'attempts' => $link->attempts,
                ]);

                // Parse HTML
                $parsed = $this->parser->parseHtml($link->url);

                if (! $parsed) {
                    throw new \RuntimeException('Parser returned empty content');
                }

                // Normalize text content
                $parsed['title'] = html_entity_decode($parsed['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $parsed['content'] = html_entity_decode($parsed['content'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $parsed['summary'] = html_entity_decode($parsed['summary'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

                // Generate uniqueness hash
                $hash = HashService::generate($parsed['title'], $parsed['content']);
                $parsed['hash'] = $hash;
                $parsed['url'] = $link->url;

                // Duplicate check using embeddings
                $check = $this->embeddingService->checkDuplicate($parsed['content']);
                $threshold = $this->embeddingService->getThreshold();

                $this->logger->log('ConsoleParseArticles', 'DEBUG', 'Embedding service response', [
                    'url' => $link->url,
                    'duplicate' => $check['duplicate'] ?? 'not_set',
                    'similarity' => $check['similarity'] ?? null,
                    'threshold' => $threshold,
                ]);

                // Error from embedding service
                if (! empty($check['error']) && $check['error'] === true) {
                    $this->logger->log('ConsoleParseArticles', 'ERROR', 'Embedding service error', [
                        'type' => 'embedding',
                        'url' => $link->url,
                        'message' => $check['message'],
                    ]);
                }
                // Article is duplicate of existing node
                elseif (($check['duplicate'] ?? false) === true) {

                    // Extract base chroma ID
                    $chromaId = $check['chroma_id'] ?? $check['parent_id'] ?? null;
                    $baseId = $chromaId ? explode('_', $chromaId)[0] : null;

                    $originalNodeId = null;

                    if ($baseId) {
                        $embedding = NodeEmbedding::where('chroma_id', $baseId)->first();
                        $originalNodeId = $embedding?->node_id;
                    }

                    $this->logger->log('ConsoleParseArticles', 'INFO', 'Duplicate article skipped', [
                        'url' => $link->url,
                        'similarity' => $check['similarity'] ?? null,
                        'duplicate_of_node' => $originalNodeId,
                        'chroma_id' => $chromaId,
                        'threshold' => $threshold,
                    ]);

                    // Mark as duplicate
                    $link->update([
                        'parsed' => 1,
                        'is_duplicate' => true,
                        'duplicate_of' => $originalNodeId,
                    ]);

                    continue;
                }

                // Save new node and embedding
                DB::transaction(function () use ($parsed, $link, $check) {

                    $node = Node::firstOrCreate(['hash' => $parsed['hash']], $parsed);

                    // Update link status
                    $link->update([
                        'parsed' => 1,
                        'last_error' => null,
                        'attempts' => $link->attempts + 1,
                        'is_duplicate' => false,
                    ]);

                    $chromaId = $check['parent_id'] ?? $check['chroma_id'] ?? null;

                    // Store embedding relation
                    if (! empty($chromaId)) {
                        NodeEmbedding::firstOrCreate([
                            'node_id' => $node->id,
                        ], [
                            'chroma_id' => $chromaId,
                            'similarity' => isset($check['similarity']) ? (float) $check['similarity'] : null,
                        ]);
                    }

                    $this->logger->log('ConsoleParseArticles', 'INFO', 'Article saved', [
                        'node_id' => $node->id,
                        'chroma_id' => $chromaId,
                        'similarity' => $check['similarity'] ?? null,
                        'url' => $link->url,
                    ]);
                });

            } catch (\Exception $e) {

                $link->increment('attempts');
                $errorText = get_class($e).': '.$e->getMessage();

                $link->update(['last_error' => $errorText]);

                $this->logger->log('ConsoleParseArticles', 'ERROR', 'Exception while processing article', [
                    'type' => 'parser',
                    'url' => $link->url,
                    'attempts' => $link->attempts,
                    'exception' => $errorText,
                ]);
            }
        }

        $this->logger->log('ConsoleParseArticles', 'INFO', 'All links processed.', [
            'type' => 'console',
        ]);
    }
}
