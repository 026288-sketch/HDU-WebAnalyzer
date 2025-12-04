<?php

namespace App\Console\Commands;

use App\Http\Controllers\ServiceController;
use App\Services\Dashboard\DashboardCounterService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

/**
 * Class DataWorkflowCommand
 *
 * Runs the full data-processing pipeline using *isolated PHP processes*.
 * Each stage is executed in a clean process to prevent:
 *  - memory leaks,
 *  - static class pollution,
 *  - caching issues between commands.
 *
 * Pipeline stages include:
 *   1. Parsing links
 *   2. Parsing full articles
 *   3. Sentiment analysis
 *   4. Tag generation
 *
 * ## Usage:
 * Run the full pipeline:
 *   php artisan data:workflow
 *
 * Add custom limits to internal steps (example: sentiment limit):
 *   php artisan data:workflow --limit=5
 *
 * ## Why isolated processes?
 * Some stages (like Puppeteer, LLM calls, or heavy DOM parsing)
 * may leave cached global state. Running each step as its own
 * subprocess guarantees consistent behaviour and stable memory usage.
 */
class DataWorkflowCommand extends Command
{
    /**
     * The command signature.
     * This command does not accept arguments itself, but individual
     * pipeline steps may define their own flags.
     */
    protected $signature = 'data:workflow';

    /**
     * Command description shown in "php artisan list"
     */
    protected $description = 'Full pipeline: parsing, analysis, tag generation (Isolated Processes)';

    public function __construct(
        private DashboardCounterService $counterService
    ) {
        parent::__construct();
    }

    /**
     * Execute the pipeline.
     *
     * Steps are executed sequentially. If any step fails (exitCode != 0),
     * the entire workflow is stopped.
     */
    public function handle(): int
    {
        $this->info('🔍 Checking required services...');

        $puppeteerStatus = ServiceController::getPuppeteerStatus();
        $pythonStatus = ServiceController::getPythonStatus();

        $hasError = false;

        // Проверяем Puppeteer
        if ($puppeteerStatus['status'] !== 'online') {
            $this->error('❌ Error: Puppeteer service is OFFLINE.');
            $this->line('   Details: '.($puppeteerStatus['message'] ?? 'Unknown error'));
            $hasError = true;
        } else {
            $this->info("   ✅ Puppeteer is online ({$puppeteerStatus['response_time']}ms)");
        }

        // Проверяем Python
        if ($pythonStatus['status'] !== 'online') {
            $this->error('❌ Error: Python service is OFFLINE.');
            $this->line('   Details: '.($pythonStatus['message'] ?? 'Unknown error'));
            $hasError = true;
        } else {
            $this->info("   ✅ Python is online ({$pythonStatus['response_time']}ms)");
        }

        // Если хоть один сервис лежит — останавливаем работу
        if ($hasError) {
            $this->newLine();
            $this->error('🛑 Workflow stopped because required services are not running.');
            $this->line('   Please start services via dashboard or manually.');

            return self::FAILURE;
        }

        $this->newLine();

        // List of pipeline stages.
        // Note: arguments are passed as an array of CLI-compatible strings.
        $steps = [
            [
                'command' => 'ConsoleParseLinks',
                'title' => 'Parsing links',
                'args' => [],
            ],
            [
                'command' => 'ConsoleParseArticles',
                'title' => 'Parsing content',
                'args' => [],
            ],
            [
                'command' => 'ConsoleAnalizeNodes',
                'title' => 'Sentiment analysis',
                'args' => ['--limit=3'],
            ],
            [
                'command' => 'ConsoleGenerateTags',
                'title' => 'Tag generation',
                'args' => ['--limit=3'],
            ],
        ];

        $totalSteps = count($steps);
        $successfulSteps = 0;

        // Resolve PHP binary and artisan path (works on Windows & Linux)
        $phpBinary = PHP_BINARY;
        $artisanPath = base_path('artisan');

        foreach ($steps as $index => $step) {
            $stepNumber = $index + 1;

            $this->info("➡️  [{$stepNumber}/{$totalSteps}] {$step['title']}...");

            // Build subprocess command:
            // Example: php.exe artisan ConsoleGenerateTags --limit=3
            $command = array_merge(
                [$phpBinary, $artisanPath, $step['command']],
                $step['args']
            );

            // Run step in an isolated process
            $result = Process::run($command, function (string $type, string $output) {
                // Stream output to console in real time
                $this->output->write($output);
            });

            // Stop workflow if step fails
            if ($result->failed()) {
                $this->error("❌ Stopped: step '{$step['title']}' failed.");

                return self::FAILURE;
            }

            $successfulSteps++;
            $this->line("   ✓ Step completed (Exit code: {$result->exitCode()})");
            $this->newLine();
        }

        $this->info("✅ All commands executed successfully! ({$successfulSteps}/{$totalSteps})");

        return self::SUCCESS;
    }
}
