<?php

namespace App\Services\Dashboard;

use App\Models\Log as AppLog;
use Illuminate\Support\Facades\DB;

/**
 * Class DashboardCounterService
 *
 * Service to manage dashboard counters including nodes, tags, sources,
 * sentiment, emotions, errors, and console script executions.
 */
class DashboardCounterService
{
    // === Constants for counter keys ===
    public const KEY_TOTAL_NODES = 'total_nodes';

    public const KEY_TOTAL_TAGS = 'total_tags';

    public const KEY_TOTAL_SOURCES = 'total_sources';

    public const KEY_NODES_PARSED = 'nodes_parsed';

    public const KEY_NODES_MISSING_CONTENT = 'nodes_missing_content';

    public const KEY_NODES_DUPLICATES = 'nodes_duplicates';

    // Sentiment counters
    public const KEY_SENTIMENT_POSITIVE = 'nodes_sentiment_positive';

    public const KEY_SENTIMENT_NEGATIVE = 'nodes_sentiment_negative';

    public const KEY_SENTIMENT_NEUTRAL = 'nodes_sentiment_neutral';

    // Emotion counters
    public const KEY_EMOTION_ANGER = 'nodes_emotion_anger';

    public const KEY_EMOTION_SADNESS = 'nodes_emotion_sadness';

    public const KEY_EMOTION_DISGUST = 'nodes_emotion_disgust';

    public const KEY_EMOTION_FEAR = 'nodes_emotion_fear';

    public const KEY_EMOTION_JOY = 'nodes_emotion_joy';

    public const KEY_EMOTION_SURPRISE = 'nodes_emotion_surprise';

    public const KEY_EMOTION_NEUTRAL = 'nodes_emotion_neutral';

    // Aggregate error counter
    public const KEY_ERRORS = 'errors';

    // Console script execution counters
    public const KEY_CONSOLE_SCRIPT_RUNS = 'console_script_runs';

    public const KEY_LAST_CONSOLE_SCRIPT_RUN = 'last_console_script_run';

    /**
     * Delta keys which should be reset after synchronization
     */
    private const DELTA_KEYS = [
        self::KEY_NODES_PARSED,
        self::KEY_NODES_MISSING_CONTENT,
        self::KEY_NODES_DUPLICATES,
        self::KEY_SENTIMENT_POSITIVE,
        self::KEY_SENTIMENT_NEGATIVE,
        self::KEY_SENTIMENT_NEUTRAL,
        self::KEY_EMOTION_ANGER,
        self::KEY_EMOTION_SADNESS,
        self::KEY_EMOTION_DISGUST,
        self::KEY_EMOTION_FEAR,
        self::KEY_EMOTION_JOY,
        self::KEY_EMOTION_SURPRISE,
        self::KEY_EMOTION_NEUTRAL,
        self::KEY_CONSOLE_SCRIPT_RUNS,
        self::KEY_ERRORS,
    ];

    /**
     * Increment the counter by a given value
     *
     * @param  string  $key  Counter key
     * @param  int  $by  Amount to increment
     */
    public function increment(string $key, int $by = 1): void
    {
        $counter = DB::table('dashboard_counters')->where('key', $key)->first();

        $value = ['count' => $by];
        if ($counter) {
            $decoded = json_decode($counter->value, true) ?: [];
            $value['count'] = ($decoded['count'] ?? 0) + $by;
        }

        DB::table('dashboard_counters')->updateOrInsert(
            ['key' => $key],
            [
                'value' => json_encode($value),
                'updated_at' => now(),
                'created_at' => $counter ? $counter->created_at : now(),
            ]
        );
    }

    /**
     * Decrement the counter by a given value
     *
     * @param  string  $key  Counter key
     * @param  int  $by  Amount to decrement
     */
    public function decrement(string $key, int $by = 1): void
    {
        $counter = DB::table('dashboard_counters')->where('key', $key)->first();

        $current = 0;
        if ($counter) {
            $decoded = json_decode($counter->value, true) ?: [];
            $current = (int) ($decoded['count'] ?? 0);
        }

        $value = ['count' => max(0, $current - $by)];

        DB::table('dashboard_counters')->updateOrInsert(
            ['key' => $key],
            [
                'value' => json_encode($value),
                'updated_at' => now(),
                'created_at' => $counter ? $counter->created_at : now(),
            ]
        );
    }

    /**
     * Update total sources and related statistics
     */
    public function updateTotalSources(): void
    {
        $total = DB::table('sources')->count();
        $rss = DB::table('sources')->whereNotNull('rss_url')->count();
        $full_rss = DB::table('sources')->where('full_rss_content', 1)->count();
        $browser = DB::table('sources')->where('need_browser', 1)->count();

        $value = [
            'count' => $total,
            'rss' => $rss,
            'full_rss' => $full_rss,
            'browser_required' => $browser,
        ];

        DB::table('dashboard_counters')->updateOrInsert(
            ['key' => self::KEY_TOTAL_SOURCES],
            [
                'value' => json_encode($value),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    /**
     * Update total tags counter
     */
    public function updateTotalTags(): void
    {
        $total = DB::table('tags')->count();

        DB::table('dashboard_counters')->updateOrInsert(
            ['key' => self::KEY_TOTAL_TAGS],
            [
                'value' => json_encode(['count' => $total]),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    /**
     * Update error aggregate when a new log is created
     */
    public function updateErrorsOnLogCreated(AppLog $log): void
    {
        $counter = DB::table('dashboard_counters')->where('key', self::KEY_ERRORS)->first();

        $value = [
            'count' => 0,
            'types' => [
                'parser' => 0,
                'console' => 0,
                'embedding' => 0,
                'ai_service' => 0,
            ],
            'last_errors' => [],
        ];

        if ($counter) {
            $decoded = json_decode($counter->value, true) ?: [];
            $value['count'] = (int) ($decoded['count'] ?? 0);
            $value['types'] = array_merge($value['types'], (array) ($decoded['types'] ?? []));
            $value['last_errors'] = (array) ($decoded['last_errors'] ?? []);
        }

        $level = strtolower((string) $log->level);
        $isErrorLevel = in_array($level, ['error', 'critical', 'alert', 'emergency'], true);
        if (! $isErrorLevel) {
            return;
        }

        $typeKey = $this->mapServiceToErrorType((string) $log->service, (array) ($log->context ?? []));

        $value['count']++;
        $value['types'][$typeKey] = (int) ($value['types'][$typeKey] ?? 0) + 1;

        $value['last_errors'][] = [
            'service' => (string) $log->service,
            'level' => (string) $log->level,
            'message' => (string) $log->message,
            'context' => $log->context ?? [],
            'timestamp' => $log->created_at ? $log->created_at->toISOString() : now()->toISOString(),
        ];

        $value['last_errors'] = array_values(array_slice($value['last_errors'], -10));

        DB::table('dashboard_counters')->updateOrInsert(
            ['key' => self::KEY_ERRORS],
            [
                'value' => json_encode($value),
                'updated_at' => now(),
                'created_at' => $counter ? $counter->created_at : now(),
            ]
        );
    }

    /**
     * Map service name to error type key
     */
    protected function mapServiceToErrorType(string $service, array $context = []): string
    {
        $serviceLower = strtolower($service);
        $contextType = strtolower((string) ($context['type'] ?? ''));

        if (in_array($contextType, ['parser', 'console', 'embedding', 'ai_service'], true)) {
            return $contextType;
        }

        if (str_contains($serviceLower, 'console')) {
            return 'console';
        }
        if (str_contains($serviceLower, 'chroma') || str_contains($serviceLower, 'embedding')) {
            return 'embedding';
        }
        if (str_contains($serviceLower, 'gemini') || str_contains($serviceLower, 'ai') || str_contains($serviceLower, 'llm')) {
            return 'ai_service';
        }

        return 'parser';
    }

    /**
     * Increment successful console script run counter
     */
    public function incrementConsoleSuccess(?string $commandName = null): void
    {
        $counter = DB::table('dashboard_counters')->where('key', self::KEY_CONSOLE_SCRIPT_RUNS)->first();

        $value = ['count' => 1];
        if ($counter) {
            $decoded = json_decode($counter->value, true) ?: [];
            $value['count'] = (int) ($decoded['count'] ?? 0) + 1;
        }

        DB::table('dashboard_counters')->updateOrInsert(
            ['key' => self::KEY_CONSOLE_SCRIPT_RUNS],
            [
                'value' => json_encode($value),
                'updated_at' => now(),
                'created_at' => $counter ? $counter->created_at : now(),
            ]
        );
    }

    /**
     * Update last console script run timestamp
     */
    public function updateLastConsoleRun(?string $commandName = null): void
    {
        $counter = DB::table('dashboard_counters')->where('key', self::KEY_LAST_CONSOLE_SCRIPT_RUN)->first();

        $value = ['timestamp' => now()->toISOString()];
        if ($commandName) {
            $value['command'] = $commandName;
        }

        DB::table('dashboard_counters')->updateOrInsert(
            ['key' => self::KEY_LAST_CONSOLE_SCRIPT_RUN],
            [
                'value' => json_encode($value),
                'updated_at' => now(),
                'created_at' => $counter ? $counter->created_at : now(),
            ]
        );
    }

    /**
     * Get all delta keys
     */
    public static function getDeltaKeys(): array
    {
        return self::DELTA_KEYS;
    }

    /**
     * Reset delta counters after synchronization
     */
    public function resetDeltaCounters(): void
    {
        DB::transaction(function () {
            foreach (self::DELTA_KEYS as $key) {
                $counter = DB::table('dashboard_counters')->where('key', $key)->first();
                if (! $counter) {
                    continue;
                }

                if ($key === self::KEY_ERRORS) {
                    $decoded = json_decode($counter->value, true) ?: [];
                    $value = [
                        'count' => 0,
                        'types' => [
                            'parser' => 0,
                            'console' => 0,
                            'embedding' => 0,
                            'ai_service' => 0,
                        ],
                        'last_errors' => $decoded['last_errors'] ?? [],
                    ];
                } else {
                    $value = ['count' => 0];
                }

                DB::table('dashboard_counters')->where('key', $key)->update([
                    'value' => json_encode($value),
                    'updated_at' => now(),
                ]);
            }
        });

        \Log::info('Delta counters reset completed', [
            'reset_keys' => self::DELTA_KEYS,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
