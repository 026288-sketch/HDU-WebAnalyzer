<?php

namespace App\Console\Commands;

use App\Constants\ParsingConstants;
use App\Repositories\NodeLinkRepository;
use App\Services\Logs\LoggerService;
use App\Services\Parser\ArticleContentParser;
use App\Services\Parser\ArticleProcessor;
use Illuminate\Console\Command;

/**
 * Class ConsoleParseArticles
 *
 * This command performs Stage 2 parsing:
 * - Loads unprocessed links from the `nodes_links` table
 * - Parses article content using ArticleContentParser
 * - Delegates business logic to ArticleProcessor
 * - Handles duplicates and saves unique articles
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
     * Processor service for unified article processing logic.
     */
    protected ArticleProcessor $processor;

    /**
     * Repository for NodeLink database operations.
     */
    protected NodeLinkRepository $repository;

    /**
     * Constructor.
     */
    public function __construct(
        ArticleContentParser $parser,
        LoggerService $logger,
        ArticleProcessor $processor,
        NodeLinkRepository $repository
    ) {
        parent::__construct();
        $this->parser = $parser;
        $this->logger = $logger;
        $this->processor = $processor;
        $this->repository = $repository;
    }

    /**
     * Execute the console command.
     *
     * Workflow:
     * - Load all unprocessed NodeLink entries using Repository
     * - Parse article HTML for each link
     * - Delegate processing to ArticleProcessor
     * - ArticleProcessor handles: normalization, hashing, deduplication, persistence
     * - Log all steps with detailed context information
     */
    public function handle(): void
    {
        $links = $this->repository->getUnprocessedWithinRetryLimit();

        if ($links->isEmpty()) {
            $this->logger->log(
                ParsingConstants::COMMAND_PARSE_ARTICLES,
                ParsingConstants::LOG_INFO,
                'No unprocessed links found.',
                ['type' => 'console']
            );
            $this->info('ℹ️ No unprocessed links found.');

            return;
        }

        $this->logger->log(
            ParsingConstants::COMMAND_PARSE_ARTICLES,
            ParsingConstants::LOG_INFO,
            'Found links to process',
            ['type' => 'console', 'count' => $links->count()]
        );

        $processed = 0;
        $successful = 0;
        $duplicates = 0;
        $errors = 0;

        foreach ($links as $link) {
            $processed++;

            try {
                $this->logger->log(
                    ParsingConstants::COMMAND_PARSE_ARTICLES,
                    ParsingConstants::LOG_INFO,
                    'Processing article',
                    ['url' => $link->url, 'attempts' => $link->attempts]
                );

                /**
                 * Parse HTML content (respect use_browser flag saved on the link)
                 */
                $useBrowser = (bool) ($link->use_browser ?? false);
                $parsed = $this->parser->parseHtml($link->url, $useBrowser);

                if (!$parsed) {
                    throw new \RuntimeException('Parser returned empty content');
                }

                /**
                 * Delegate all business logic to ArticleProcessor
                 * Handles: normalization, hashing, deduplication, persistence, logging
                 */
                $success = $this->processor->processArticle(
                    $link,
                    $parsed,
                    ParsingConstants::SOURCE_HTML
                );

                if ($success) {
                    $successful++;
                } else {
                    $errors++;
                }

            } catch (\Exception $e) {
                $this->logger->log(
                    ParsingConstants::COMMAND_PARSE_ARTICLES,
                    ParsingConstants::LOG_ERROR,
                    'Exception while processing article',
                    [
                        'url' => $link->url,
                        'exception' => get_class($e),
                        'error' => $e->getMessage(),
                    ]
                );

                $this->repository->recordError(
                    $link,
                    $e->getMessage(),
                    ParsingConstants::ERROR_PARSING
                );

                $errors++;
            }
        }

        /**
         * Final summary logging
         */
        $this->logger->log(
            ParsingConstants::COMMAND_PARSE_ARTICLES,
            ParsingConstants::LOG_INFO,
            'All links processed.',
            [
                'type' => 'console',
                'total_processed' => $processed,
                'successful' => $successful,
                'errors' => $errors,
            ]
        );

        $this->info("✅ Processing completed: {$successful} articles processed, {$errors} errors");
    }
}
