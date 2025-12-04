<?php

namespace App\Console\Commands;

use App\Models\Node;
use App\Services\Logs\LoggerService;
use App\Services\TagGeneratorService;
use Illuminate\Console\Command;

class ConsoleGenerateTags extends Command
{
    /**
     * Command signature with optional limit parameter.
     *
     * @var string
     */
    protected $signature = 'ConsoleGenerateTags {--limit=3 : Number of articles to process}';

    /**
     * Short description of the command.
     *
     * @var string
     */
    protected $description = 'Generate tags for articles that do not have any tags assigned';

    /**
     * Service responsible for generating tags using AI.
     */
    protected TagGeneratorService $service;

    /**
     * Service for logging detailed execution info.
     */
    protected LoggerService $logger;

    /**
     * Constructor.
     *
     * Initializes the tag generator and logger services.
     */
    public function __construct()
    {
        parent::__construct();
        $this->logger = new LoggerService;
        $this->service = new TagGeneratorService($this->logger);
    }

    /**
     * Execute the console command.
     *
     * Workflow:
     * - Load the limit of nodes to process
     * - Log initial state
     * - Fetch nodes that have no tags
     * - Generate tags for each node via TagGeneratorService
     * - Log results and output summary
     *
     * @return int Command exit code
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        // Count how many nodes require tag generation before starting
        $pendingBefore = Node::whereDoesntHave('tags')->count();
        $this->logger->log('ConsoleGenerateTags', 'INFO', 'Starting tag generation', [
            'type' => 'ai_service',
            'limit' => $limit,
            'pending' => $pendingBefore,
        ]);

        // Load nodes without any tags
        $nodes = Node::whereDoesntHave('tags')->take($limit)->get();

        if ($nodes->isEmpty()) {
            $this->info('There are no articles without tags.');

            return 0;
        }

        $this->info('Launching tag generation...');

        $processed = 0;

        // Process each node individually
        foreach ($nodes as $node) {
            try {
                $this->service->generateTags($node);
                $this->info("✅ Node {$node->id}: tags generated successfully");
                $processed++;
            } catch (\Exception $e) {
                $this->error("❌ Node {$node->id}: {$e->getMessage()}");
            }
        }

        // Count remaining nodes without tags
        $remaining = Node::whereDoesntHave('tags')->count();
        $this->logger->log('ConsoleGenerateTags', 'INFO', 'Tag generation completed', [
            'type' => 'ai_service',
            'processed' => $processed,
            'remaining' => $remaining,
        ]);

        $this->info("Completed. Remaining articles without tags: {$remaining}");

        return 0;
    }
}
