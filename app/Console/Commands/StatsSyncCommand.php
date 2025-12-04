<?php

namespace App\Console\Commands;

use App\Services\StatsSyncService;
use Illuminate\Console\Command;

/**
 * Class StatsSyncCommand
 *
 * This console command synchronizes dashboard counters
 * into aggregated daily and monthly statistics.
 *
 * Workflow:
 * - Uses StatsSyncService to calculate totals, deltas, and trends
 * - Updates daily and monthly summary tables
 * - Logs process start and completion in the console
 * - Handles exceptions and provides success/failure feedback
 *
 * Intended to be run as a scheduled task or manually via artisan.
 */
class StatsSyncCommand extends Command
{
    /**
     * Console command signature.
     *
     * @var string
     */
    protected $signature = 'stats:sync';

    /**
     * Short description of the command.
     *
     * @var string
     */
    protected $description = 'Sync dashboard counters to daily/monthly statistics';

    /**
     * Service responsible for synchronizing statistics.
     */
    private StatsSyncService $syncService;

    /**
     * Constructor.
     *
     * Injects the StatsSyncService dependency.
     */
    public function __construct(StatsSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    /**
     * Execute the console command.
     *
     * @return int Exit code
     */
    public function handle(): int
    {
        $this->info('Starting statistics synchronization...');

        try {
            // Run the sync process
            $this->syncService->sync();

            $this->info('✅ Statistics synchronized successfully!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            // Log any errors to the console
            $this->error('❌ Error during synchronization: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
