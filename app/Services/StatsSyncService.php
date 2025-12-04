<?php

namespace App\Services;

use App\Services\Dashboard\DashboardCounterService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service for synchronizing statistics (daily and monthly).
 *
 * Designed to be executed from a cron job.
 */
class StatsSyncService
{
    /**
     * Main synchronization method (to be called from cron).
     */
    public function sync(): void
    {
        try {
            $today = Carbon::today()->toDateString();
            $month = Carbon::today()->format('Y-m');

            // 1. Synchronize daily stats + reset delta counters
            DB::transaction(function () use ($today) {
                // Take a snapshot BEFORE resetting deltas
                $dailyData = $this->getDashboardCountersSnapshot();

                $payload = [
                    'data' => json_encode($dailyData),
                    'is_synced' => 1,
                    'updated_at' => now(),
                ];

                $existing = DB::table('stats_daily')->where('date', $today)->first();

                if ($existing) {
                    DB::table('stats_daily')->where('date', $today)->update($payload);
                } else {
                    DB::table('stats_daily')->insert($payload + ['date' => $today, 'created_at' => now()]);
                }

                // Reset delta counters AFTER saving snapshot
                app(DashboardCounterService::class)->resetDeltaCounters();
            });

            // 2. Synchronize monthly stats (aggregate from daily)
            DB::transaction(function () use ($month) {
                $dailyStats = DB::table('stats_daily')
                    ->where('date', 'like', "$month%")
                    ->orderBy('date', 'asc')
                    ->get();

                if ($dailyStats->isEmpty()) {
                    return;
                }

                $monthlyData = $this->aggregateDailyStats($dailyStats);

                $payload = [
                    'data' => json_encode($monthlyData),
                    'is_synced' => 1,
                    'updated_at' => now(),
                ];

                $existing = DB::table('stats_monthly')->where('month', $month)->first();

                if ($existing) {
                    DB::table('stats_monthly')->where('month', $month)->update($payload);
                } else {
                    DB::table('stats_monthly')->insert($payload + ['month' => $month, 'created_at' => now()]);
                }
            });

            \Log::info('Stats sync completed successfully', [
                'date' => $today,
                'deltas_reset' => true,
            ]);

        } catch (\Exception $e) {
            \Log::error('Stats sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Take a snapshot of all dashboard counters.
     */
    private function getDashboardCountersSnapshot(): array
    {
        $counters = DB::table('dashboard_counters')->get();

        $snapshot = [];
        foreach ($counters as $counter) {
            $snapshot[$counter->key] = json_decode($counter->value, true);
        }

        return $snapshot;
    }

    /**
     * Aggregate daily stats into monthly stats.
     */
    private function aggregateDailyStats(Collection $dailyStats): array
    {
        $monthly = [];
        $summableKeys = DashboardCounterService::getDeltaKeys();

        // 1. Take the last values for all totals
        $lastDayData = json_decode($dailyStats->last()->data, true) ?: [];

        foreach ($lastDayData as $key => $value) {
            if (! in_array($key, $summableKeys)) {
                $monthly[$key] = $value;
            }
        }

        // 2. Sum up delta values
        foreach ($dailyStats as $day) {
            $data = json_decode($day->data, true);
            if (! $data) {
                continue;
            }

            foreach ($summableKeys as $key) {
                if (! isset($data[$key])) {
                    continue;
                }

                if ($key === DashboardCounterService::KEY_ERRORS) {
                    continue;
                }

                if ($key === DashboardCounterService::KEY_CONSOLE_SCRIPT_RUNS) {
                    $monthly[$key] = $this->mergeConsoleRuns(
                        $monthly[$key] ?? [],
                        $data[$key]
                    );
                } elseif (isset($data[$key]['count'])) {
                    $currentCount = $monthly[$key]['count'] ?? 0;
                    $monthly[$key]['count'] = $currentCount + ($data[$key]['count'] ?? 0);
                } elseif (is_numeric($data[$key])) {
                    $monthly[$key] = ($monthly[$key] ?? 0) + $data[$key];
                }
            }
        }

        // 3. Handle 'errors' separately
        foreach ($dailyStats as $day) {
            $data = json_decode($day->data, true);
            if (! $data || ! isset($data[DashboardCounterService::KEY_ERRORS])) {
                continue;
            }

            $monthly[DashboardCounterService::KEY_ERRORS] = $this->mergeErrorStats(
                $monthly[DashboardCounterService::KEY_ERRORS] ?? [],
                $data[DashboardCounterService::KEY_ERRORS]
            );
        }

        return $monthly;
    }

    /**
     * Merge daily console script runs into monthly aggregate.
     *
     * @param  mixed  $dayRuns
     */
    private function mergeConsoleRuns(array $monthlyRuns, $dayRuns): array
    {
        if (is_array($dayRuns)) {
            foreach ($dayRuns as $command => $count) {
                if ($command === 'total') {
                    $monthlyRuns['total'] = ($monthlyRuns['total'] ?? 0) + $count;
                } elseif (is_string($command)) {
                    $monthlyRuns[$command] = ($monthlyRuns[$command] ?? 0) + $count;
                }
            }
        } elseif (is_numeric($dayRuns)) {
            $monthlyRuns = ($monthlyRuns ?? 0) + $dayRuns;
        }

        return $monthlyRuns;
    }

    /**
     * Merge daily error stats into monthly aggregate.
     */
    private function mergeErrorStats(array $monthlyErrors, array $dayErrors): array
    {
        $monthlyErrors['count'] = ($monthlyErrors['count'] ?? 0) + ($dayErrors['count'] ?? 0);

        if (! isset($monthlyErrors['types'])) {
            $monthlyErrors['types'] = [];
        }
        if (isset($dayErrors['types']) && is_array($dayErrors['types'])) {
            foreach ($dayErrors['types'] as $type => $count) {
                $monthlyErrors['types'][$type] = ($monthlyErrors['types'][$type] ?? 0) + (int) $count;
            }
        }

        $allErrors = array_merge($monthlyErrors['last_errors'] ?? [], $dayErrors['last_errors'] ?? []);

        usort($allErrors, function ($a, $b) {
            $timeA = strtotime($a['timestamp'] ?? '1970-01-01');
            $timeB = strtotime($b['timestamp'] ?? '1970-01-01');

            return $timeB <=> $timeA;
        });

        $monthlyErrors['last_errors'] = array_slice($allErrors, 0, 10);

        return $monthlyErrors;
    }
}
