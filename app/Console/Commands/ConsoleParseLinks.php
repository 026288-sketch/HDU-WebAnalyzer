<?php

namespace App\Console\Commands;

use App\Constants\ParsingConstants;
use App\Models\NodeLink;
use App\Repositories\NodeLinkRepository;
use App\Services\EmbeddingService;
use App\Services\Logs\LoggerService;
use App\Services\Parser\ArticleContentParser;
use App\Services\Parser\ArticleFinder;
use App\Services\Parser\ArticleProcessor;
use App\Services\Source\SourceService;
use Illuminate\Console\Command;

/**
 * Class ConsoleParseLinks
 *
 * This command performs Stage 1 parsing:
 * - Rotates active source
 * - Extracts article URLs via regex or RSS
 * - Saves unique raw links into the `article_links` table
 * - For full RSS feeds, parses content immediately using ArticleProcessor
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

    protected ArticleProcessor $processor;

    protected NodeLinkRepository $repository;

    /**
     * Inject required services.
     */
    public function __construct(
        ArticleFinder $finder,
        ArticleContentParser $parser,
        LoggerService $logger,
        SourceService $sourceService,
        EmbeddingService $embeddingService,
        ArticleProcessor $processor,
        NodeLinkRepository $repository
    ) {
        parent::__construct();
        $this->finder = $finder;
        $this->parser = $parser;
        $this->logger = $logger;
        $this->sourceService = $sourceService;
        $this->embeddingService = $embeddingService;
        $this->processor = $processor;
        $this->repository = $repository;
    }

    /**
     * Handle the command execution.
     *
     * Workflow:
     * 1. Rotate active source
     * 2. Load regex (from DB or file)
     * 3. Parse links via Regex / HTML or RSS
     * 4. Save new links into `article_links`
     * 5. If RSS contains full content — parse full article immediately using ArticleProcessor
     */
    public function handle()
    {
        /**
         * Step 1 — Fetch and rotate the active source.
         * If no sources exist, stop the process.
         */
        $source = $this->sourceService->rotateActiveSource();
        if (! $source) {
            $this->logger->log(
                ParsingConstants::COMMAND_PARSE_LINKS,
                ParsingConstants::LOG_ERROR,
                'No sources found in the database.',
                ['type' => 'console']
            );
            $this->info('ℹ️ No sources found in the database.');

            return;
        }

        $this->logger->log(
            ParsingConstants::COMMAND_PARSE_LINKS,
            ParsingConstants::LOG_INFO,
            'Parsing links from source',
            ['url' => $source->url]
        );

        /**
         * Step 2 — Determine which regex to use.
         */
        $regexPath = storage_path('app/regex.txt');
        $regex = $source->regex ?? (file_exists($regexPath) ? trim(file_get_contents($regexPath)) : null);

        if (! $regex) {
            $this->logger->log(
                ParsingConstants::COMMAND_PARSE_LINKS,
                ParsingConstants::LOG_ERROR,
                'Regex not found or empty.',
                ['type' => 'console', 'path' => $regexPath]
            );
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
                 * Full RSS content — parse full article immediately using ArticleProcessor
                 */
                $this->info('ℹ️ Processing full RSS content');
                foreach ($items as $item) {
                    $linksSaved += $this->processRssItemWithProcessor($item, $source);
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
         * Step 5 — Save new unique links into DB using Repository
         * Applies to:
         * - HTML parsing
         * - RSS without full content
         */
        if ($links->isNotEmpty()) {
            $linksSaved += $this->repository->saveNewLinks($links, $source, ParsingConstants::TYPE_HTML);
        }

        /**
         * Final logging
         */
        $this->logger->log(
            ParsingConstants::COMMAND_PARSE_LINKS,
            ParsingConstants::LOG_INFO,
            'Parsing completed',
            ['source' => $source->url, 'links_saved' => $linksSaved]
        );

        $this->info("✅ Parsing completed for source: $source->url, total links saved: $linksSaved");
    }

    /**
     * Process a single RSS item with full content using ArticleProcessor
     *
     * @param  array  $item  RSS item data
     * @param  mixed  $source  Current source model
     * @return int Number of articles saved (0 or 1)
     */
    protected function processRssItemWithProcessor(array $item, $source): int
    {
        /**
         * FirstOrCreate ensures URL uniqueness in links table
         */
        $linkModel = NodeLink::firstOrCreate(
            ['url' => $item['link']],
            [
                'parsed' => ParsingConstants::STATUS_UNPARSED,
                'source' => $source->url,
                'type' => ParsingConstants::TYPE_RSS,
                'attempts' => 0,
                'use_browser' => $source->need_browser ?? 0,
                'last_error' => null,
                'is_duplicate' => false,
                'duplicate_of' => null,
            ]
        );

        /**
         * Skip already processed items (unless they have errors)
         */
        if ($linkModel->parsed == ParsingConstants::STATUS_PARSED && empty($linkModel->last_error)) {
            return 0;
        }

        /**
         * Skip items that exceeded retry limit
         */
        if ($linkModel->attempts >= ParsingConstants::MAX_RETRY_ATTEMPTS) {
            $this->logger->log(
                ParsingConstants::COMMAND_PARSE_LINKS,
                ParsingConstants::LOG_WARNING,
                'Skip article (too many attempts)',
                ['url' => $item['link'], 'attempts' => $linkModel->attempts]
            );

            return 0;
        }

        try {
            /**
             * Parse content from RSS item
             */
            $parsed = $this->parser->parseRss($item['itemXml']);

            if (! $parsed) {
                throw new \RuntimeException('Parser returned empty content');
            }

            /**
             * Use ArticleProcessor for unified business logic
             */
            $success = $this->processor->processArticle(
                $linkModel,
                $parsed,
                ParsingConstants::SOURCE_RSS
            );

            return $success ? 1 : 0;

        } catch (\Exception $e) {
            /**
             * Handle parsing errors
             */
            $this->logger->log(
                ParsingConstants::COMMAND_PARSE_LINKS,
                ParsingConstants::LOG_ERROR,
                'Error parsing RSS item',
                [
                    'url' => $item['link'],
                    'error' => $e->getMessage(),
                ]
            );

            $this->repository->recordError(
                $linkModel,
                $e->getMessage(),
                ParsingConstants::ERROR_PARSING
            );

            return 0;
        }
    }
}
