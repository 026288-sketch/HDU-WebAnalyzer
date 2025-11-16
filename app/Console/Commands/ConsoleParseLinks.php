<?php

namespace App\Console\Commands;

use App\Models\Node;
use App\Models\NodeEmbedding;
use App\Models\NodeLink;
use App\Services\EmbeddingService;
use App\Services\Logs\LoggerService;
use App\Services\Parser\ArticleContentParser;
use App\Services\Parser\ArticleFinder;
use App\Services\Parser\HashService;
use App\Services\Source\SourceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Class ConsoleParseLinks
 *
 * This command performs Stage 1 parsing:
 * - Rotates active source
 * - Extracts article URLs via regex or RSS
 * - Saves unique raw links into the `article_links` table
 * - For full RSS feeds, parses content immediately
 *
 * It is used as the entry point for the multi-stage parsing pipeline.
 */
class ConsoleParseLinks extends Command
{
    protected $signature = 'ConsoleParseLinks {regex?}';

    protected $description = 'Parse links from active sources and save them into article_links table';

    protected ArticleFinder $finder;

    protected ArticleContentParser $parser;

    protected LoggerService $logger;

    protected SourceService $sourceService;

    protected EmbeddingService $embeddingService;

    /**
     * Inject required services.
     */
    public function __construct(
        ArticleFinder $finder,
        ArticleContentParser $parser,
        LoggerService $logger,
        SourceService $sourceService,
        EmbeddingService $embeddingService
    ) {
        parent::__construct();
        $this->finder = $finder;
        $this->parser = $parser;
        $this->logger = $logger;
        $this->sourceService = $sourceService;
        $this->embeddingService = $embeddingService;
    }

    /**
     * Handle the command execution.
     *
     * Workflow:
     * 1. Rotate active source
     * 2. Load regex (from DB or file)
     * 3. Parse links via Regex / HTML or RSS
     * 4. Save new links into `article_links`
     * 5. If RSS contains full content — parse full article immediately
     */
    public function handle()
    {
        /**
         * Step 1 — Fetch and rotate the active source.
         * If no sources exist, stop the process.
         */
        $source = $this->sourceService->rotateActiveSource();
        if (! $source) {
            $this->logger->log('ConsoleParseLinks', 'ERROR', 'No sources found in the database.', [
                'type' => 'console',
            ]);
            $this->info('ℹ️ No sources found in the database.');

            return;
        }

        $this->logger->log('ConsoleParseLinks', 'INFO', 'Parsing links from source', [
            'url' => $source->url,
        ]);

        /**
         * Step 2 — Determine which regex to use.
         */
        $regexPath = storage_path('app/regex.txt');
        $regex = $source->regex ?? (file_exists($regexPath) ? trim(file_get_contents($regexPath)) : null);

        if (! $regex) {
            $this->logger->log('ConsoleParseLinks', 'ERROR', 'Regex not found or empty.', [
                'type' => 'console',
                'path' => $regexPath,
            ]);
            $this->info('❌ Regex not found or empty.');

            return;
        }

        $links = collect([]);
        $linksSaved = 0;

        /**
         * Step 3 — RSS Parsing
         * If the source has an RSS URL, use RSS finder instead of HTML parsing.
         */
        if (! empty($source->rss_url)) {
            $this->info('ℹ️ Processing RSS content');

            $result = $this->finder->findArticlesByRss($source->rss_url, $regex);
            $items = $result['items'] ?? [];

            if ($source->full_rss_content) {
                /**
                 * Full RSS content — parse full article immediately.
                 */
                $this->info('ℹ️ Processing full RSS content');
                foreach ($items as $item) {
                    $this->processRssItem($item, $linksSaved, $source);
                }
            } else {
                /**
                 * RSS without full content — just collect URLs.
                 */
                $this->info('ℹ️ Processing RSS and saving links...');
                foreach ($items as $item) {
                    $links->push($item['link']);
                }
            }

        } else {
            /**
             * Step 4 — HTML Parsing (standard regex-based article discovery)
             */
            $this->info('ℹ️ Processing HTML and saving links...');

            $linksResult = $this->finder->findArticlesByRegex(
                $source->url,
                $regex,
                $source->need_browser
            );

            $links = collect($linksResult['articles']);
        }

        /**
         * Step 5 — Save new unique links into DB
         * Applies to:
         * - HTML parsing
         * - RSS without full content
         */
        if ($links->isNotEmpty()) {
            $existingLinks = NodeLink::whereIn('url', $links)->pluck('url')->toArray();
            $newLinks = $links->diff($existingLinks);

            foreach ($newLinks as $link) {
                NodeLink::create([
                    'url' => $link,
                    'parsed' => 0,
                    'source' => $source->url ?? 'unknown',
                    'type' => 'html',
                    'use_browser' => $source->need_browser,
                    'attempts' => 0,
                    'last_error' => null,
                ]);

                $linksSaved++;
            }
        }

        /**
         * Final logging
         */
        $this->logger->log('ConsoleParseLinks', 'INFO', 'Parsing completed', [
            'source' => $source->url,
            'links_saved' => $linksSaved,
        ]);

        $this->info("✅ Parsing completed for source: $source->url, total links saved: $linksSaved");
    }

    /**
     * Process a single RSS item with full content.
     *
     * Handles:
     * - Deduplication
     * - Hashing
     * - Node creation
     * - NodeEmbedding linking
     * - Error handling & retry counter
     *
     * @param  array  $item  RSS item data
     * @param  int  $linksSaved  Reference counter for saved links
     * @param  mixed  $source  Current source model
     */
    protected function processRssItem(array $item, int &$linksSaved, $source): void
    {
        /**
         * FirstOrCreate ensures URL uniqueness.
         */
        $linkModel = NodeLink::firstOrCreate(
            ['url' => $item['link']],
            [
                'parsed' => 0,
                'source' => $source->url ?? 'unknown',
                'type' => 'rss',
                'use_browser' => 0,
                'attempts' => 0,
                'last_error' => null,
                'is_duplicate' => false,
                'duplicate_of' => null,
            ]
        );

        /**
         * Skip successfully parsed items
         */
        if ($linkModel->parsed == 1 && empty($linkModel->last_error)) {
            return;
        }

        /**
         * Skip items that exceeded retry limit
         */
        if ($linkModel->attempts >= 3) {
            $this->logger->log('ConsoleParseLinks', 'WARNING', 'Skip article (too many attempts)', [
                'url' => $item['link'],
                'attempts' => $linkModel->attempts,
            ]);

            return;
        }

        try {
            /**
             * Step 1 — Parse content from RSS item
             */
            $parsed = $this->parser->parseRss($item['itemXml']);

            if ($parsed) {
                /**
                 * Step 2 — Generate hash + deduplication
                 */
                $hash = HashService::generate($parsed['title'], $parsed['content']);
                $parsed['hash'] = $hash;

                $check = $this->embeddingService->checkDuplicate(
                    $parsed['content'],
                    $parsed['summary'] ?? null
                );

                $threshold = $this->embeddingService->getThreshold();

                $this->logger->log('ConsoleParseLinks', 'DEBUG', 'Embedding service response', [
                    'url' => $item['link'],
                    'duplicate' => $check['duplicate'] ?? 'not_set',
                    'similarity' => $check['similarity'] ?? null,
                    'threshold' => $threshold,
                ]);

                /**
                 * Step 3 — Handle embedding service failure
                 */
                if (! empty($check['error']) && $check['error'] === true) {
                    $this->logger->log('ConsoleParseLinks', 'ERROR', 'Embedding service error', [
                        'type' => 'embedding',
                        'url' => $item['link'],
                        'message' => $check['message'],
                    ]);
                }
                /**
                 * Step 4 — Handle detected duplicate
                 */
                elseif (isset($check['duplicate']) && $check['duplicate'] === true) {

                    // Determine original Node ID
                    $chromaId = $check['chroma_id'] ?? $check['parent_id'] ?? null;
                    $baseId = explode('_', $chromaId)[0];
                    $originalNodeId = null;

                    if ($chromaId) {
                        $embedding = NodeEmbedding::where('chroma_id', $baseId)->first();
                        $originalNodeId = $embedding?->node_id;
                    }

                    $this->logger->log('ConsoleParseLinks', 'INFO', 'Duplicate article skipped', [
                        'url' => $item['link'],
                        'similarity' => $check['similarity'] ?? null,
                        'duplicate_of_node' => $originalNodeId,
                        'chroma_id' => $chromaId,
                        'threshold' => $threshold,
                    ]);

                    // Mark as duplicate
                    $linkModel->increment('attempts');
                    $linkModel->update([
                        'parsed' => 1,
                        'is_duplicate' => true,
                        'duplicate_of' => $originalNodeId,
                    ]);

                    return;
                }

                /**
                 * Step 5 — Save Node + Embedding inside transaction
                 */
                DB::transaction(function () use ($parsed, $linkModel, &$linksSaved, $check) {

                    $node = Node::firstOrCreate(['hash' => $parsed['hash']], $parsed);

                    if ($node->wasRecentlyCreated) {
                        $this->info("✅ New node added: ID {$node->id}");
                    }

                    // Update link record
                    $linkModel->increment('attempts');
                    $linkModel->update([
                        'parsed' => 1,
                        'last_error' => null,
                        'is_duplicate' => false,
                    ]);

                    // Add embedding link
                    $chromaId = $check['chroma_id'] ?? $check['parent_id'] ?? null;

                    if (! empty($chromaId)) {
                        NodeEmbedding::firstOrCreate(
                            ['node_id' => $node->id],
                            [
                                'chroma_id' => $chromaId,
                                'similarity' => isset($check['similarity']) ? (float) $check['similarity'] : null,
                            ]
                        );

                        $this->logger->log('ConsoleParseLinks', 'INFO', 'Added new node embedding', [
                            'node_id' => $node->id,
                            'chroma_id' => $chromaId,
                            'parent_id' => $check['parent_id'] ?? null,
                            'chunk_ids' => $check['chunk_ids'] ?? null,
                            'similarity' => $check['similarity'] ?? null,
                            'url' => $linkModel->url,
                        ]);
                    }

                    if ($linkModel->wasRecentlyCreated) {
                        $linksSaved++;
                    }
                });
            }

        } catch (\Exception $e) {

            /**
             * Step 6 — Handle parsing errors & increase attempt counter
             */
            $linkModel->increment('attempts');
            $linkModel->update(['last_error' => $e->getMessage()]);

            $this->logger->log('ConsoleParseLinks', 'ERROR', 'Error parsing RSS item', [
                'type' => 'parser',
                'url' => $item['link'],
                'error' => $e->getMessage(),
                'attempts' => $linkModel->attempts,
            ]);
        }
    }
}
