<?php

namespace App\Console\Commands;

use App\Models\Node;
use App\Services\AnalysisService;
use App\Services\Logs\LoggerService;
use Illuminate\Console\Command;

/**
 * Console command for performing sentiment and emotion analysis
 * on articles (Nodes) that haven't been processed yet.
 *
 * It uses AnalysisService to run AI-based classification and stores
 * the results in the database.
 */
class ConsoleAnalizeNodes extends Command
{
    /**
     * Command signature for Artisan.
     *
     * --limit option defines how many unprocessed nodes to analyze in one run.
     *
     * Example:
     *   php artisan ConsoleAnalizeNodes --limit=10
     */
    protected $signature = 'ConsoleAnalizeNodes {--limit=3 : Number of nodes to process}';

    /**
     * Description displayed in "php artisan list".
     */
    protected $description = 'Sentiment and emotion analysis of unprocessed articles';

    /**
     * AI analysis service.
     */
    protected AnalysisService $service;

    /**
     * Custom application logger.
     */
    protected LoggerService $logger;

    /**
     * Constructor initializes logger and service dependencies.
     */
    public function __construct()
    {
        parent::__construct();

        // Logger instance for storing structured logs
        $this->logger = new LoggerService;

        // AnalysisService receives the logger to track AI processing details
        $this->service = new AnalysisService($this->logger);
    }

    /**
     * Main command execution handler.
     *
     * @return int
     */
    public function handle()
    {
        // Convert option to integer. This defines how many nodes we analyze this run.
        $limit = (int) $this->option('limit');

        // Count how many nodes still haven't been analyzed
        $pendingBefore = Node::whereDoesntHave('sentiment')->count();

        // Log start of analysis
        $this->logger->log('ConsoleAnalizeNodes', 'INFO', 'Starting nodes analysis', [
            'type' => 'ai_service',
            'limit' => $limit,
            'pending' => $pendingBefore,
        ]);

        // Get unprocessed nodes, limited by user-defined number
        $nodes = Node::whereDoesntHave('sentiment')->take($limit)->get();

        // If nothing to analyze — exit gracefully
        if ($nodes->isEmpty()) {
            $this->info('There are no unprocessed articles.');

            return 0;
        }

        $this->info('Launching context (sentiment/emotion) analysis...');

        $processed = 0;

        // Process each node one-by-one
        foreach ($nodes as $node) {
            try {
                // Perform sentiment/emotion extraction
                $this->service->analyzeNode($node);

                $this->info("✅ Node {$node->id}: analysis completed successfully");
                $processed++;
            } catch (\Exception $e) {
                $this->error("❌ Node {$node->id}: {$e->getMessage()}");
            }
        }

        // Count remaining nodes after processing
        $remaining = Node::whereDoesntHave('sentiment')->count();

        // Log final summary
        $this->logger->log('ConsoleAnalizeNodes', 'INFO', 'Nodes analysis completed', [
            'type' => 'ai_service',
            'processed' => $processed,
            'remaining' => $remaining,
        ]);

        // Output final message to console
        $this->info("Analysis completed. Remaining unprocessed articles: {$remaining}");

        return 0;
    }
}
