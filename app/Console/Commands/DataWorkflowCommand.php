<?php

namespace App\Console\Commands;

use App\Services\Dashboard\DashboardCounterService;
use Illuminate\Console\Command;

/**
 * This command runs the full data-processing pipeline:
 * 1. Parse links
 * 2. Parse article content
 * 3. Run sentiment analysis
 * 4. Generate tags
 *
 * It also records the results of each step in DashboardCounterService.
 */
class DataWorkflowCommand extends Command
{
    /**
     * Command signature for Artisan.
     *
     * Usage:
     *   php artisan data:workflow
     */
    protected $signature = 'data:workflow';

    /**
     * Description displayed in "php artisan list".
     */
    protected $description = 'Full pipeline: parsing, analysis, tag generation';

    /**
     * Inject DashboardCounterService for recording progress of each step.
     */
    public function __construct(
        private DashboardCounterService $counterService
    ) {
        parent::__construct();
    }

    /**
     * Execute the entire workflow.
     */
    public function handle(): int
    {
        /**
         * Pipeline steps to execute in order.
         *
         * Each item contains:
         *  - command => name of the Artisan command to run
         *  - title   => human-readable name shown in console
         *  - args    => additional CLI arguments for the command
         */
        $steps = [
            ['command' => 'ConsoleParseLinks',     'title' => 'Parsing links',       'args' => []],
            ['command' => 'ConsoleParseArticles',  'title' => 'Parsing content',     'args' => []],
            ['command' => 'ConsoleAnalizeNodes',   'title' => 'Sentiment analysis',  'args' => ['--limit' => 3]],
            ['command' => 'ConsoleGenerateTags',   'title' => 'Tag generation',      'args' => ['--limit' => 3]],
        ];

        $totalSteps = count($steps);
        $successfulSteps = 0;

        // Run each step sequentially
        foreach ($steps as $index => $step) {
            $stepNumber = $index + 1;

            // Display progress in terminal
            $this->info("➡️  [{$stepNumber}/{$totalSteps}] {$step['title']}");

            /**
             * Execute the Artisan command.
             * $this->call(...) returns exit code:
             *   0   => OK
             *   >0  => Error
             */
            $exitCode = $this->call($step['command'], $step['args'] ?? []);

            // Stop workflow if any step fails
            if ($exitCode !== 0) {
                $this->error("❌ Stopped: step '{$step['title']}' finished with code {$exitCode}");
                $this->warn("📊 Successfully completed: {$successfulSteps}/{$totalSteps} steps");

                return $exitCode;
            }

            // Record successful execution in dashboard statistics
            $this->counterService->incrementConsoleSuccess($step['command']);
            $this->counterService->updateLastConsoleRun($step['command']);

            $successfulSteps++;

            $this->line('   ✓ Completed successfully');
        }

        // Final success message
        $this->info("✅ All commands executed successfully! ({$successfulSteps}/{$totalSteps})");

        return self::SUCCESS;
    }
}
