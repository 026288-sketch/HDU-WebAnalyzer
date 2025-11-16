<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Class StatsTestCommand
 *
 * This console command generates test statistics for dashboard counters
 * using historical data. It simulates:
 * - Number of parsed articles (nodes)
 * - Duplicate articles
 * - Sentiment analysis (positive, negative, neutral)
 * - Emotion distribution (anger, joy, fear, sadness, disgust, surprise, neutral)
 * - Errors per service (parser, AI service, embedding, console)
 * - Console script runs
 *
 * Workflow:
 * 1. Loop over the last N days (configurable via --days option)
 * 2. Generate random test data for each day
 * 3. Save daily stats into `stats_daily` table
 * 4. Aggregate monthly statistics
 * 5. Save monthly stats into `stats_monthly` table
 * 6. Display summary of generated records in the console
 *
 * Useful for testing the analytics pipeline and dashboard counters
 * without real articles.
 */
class StatsTestCommand extends Command
{
    /**
     * Command signature with optional days parameter.
     *
     * @var string
     */
    protected $signature = 'stats:test {--days=30 : Number of days to generate}';

    /**
     * Short description of the command.
     *
     * @var string
     */
    protected $description = 'Test statistics flow with historical data including errors and duplicates';

    /**
     * Execute the console command.
     *
     * @return int Exit code
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');

        $this->info("Generating test data for {$days} days...");

        $monthlyData = [];

        // Loop over days in descending order
        for ($i = $days; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $monthKey = $date->format('Y-m');

            $this->line("  Processing {$date->format('Y-m-d')}...");

            // 🔹 Generate random daily data
            $nodesParsed = rand(50, 200);
            $nodesDuplicates = rand(5, 30);
            $sentimentPositive = rand(20, 80);
            $sentimentNegative = rand(10, 50);
            $sentimentNeutral = rand(30, 100);

            // Emotions
            $emotionAnger = rand(5, 30);
            $emotionJoy = rand(10, 40);
            $emotionFear = rand(5, 25);
            $emotionSadness = rand(10, 35);
            $emotionDisgust = rand(5, 20);
            $emotionSurprise = rand(5, 30);
            $emotionNeutral = rand(20, 60);

            // 🔹 Errors
            $errorsCount = rand(0, 10);
            $errorTypes = [
                'parser' => rand(0, min(3, $errorsCount)),
                'ai_service' => rand(0, min(3, $errorsCount)),
                'embedding' => rand(0, min(2, $errorsCount)),
                'console' => rand(0, min(2, $errorsCount)),
            ];
            $consoleRuns = rand(0, 10);

            // Adjust sum of error types to match total
            $totalTypes = array_sum($errorTypes);
            if ($totalTypes > $errorsCount) {
                $diff = $totalTypes - $errorsCount;
                $errorTypes['parser'] = max(0, $errorTypes['parser'] - $diff);
            } elseif ($totalTypes < $errorsCount) {
                $errorTypes['parser'] += ($errorsCount - $totalTypes);
            }

            // Generate last error messages
            $lastErrors = [];
            $errorMessages = [
                'Parser timeout on article extraction',
                'AI service rate limit exceeded',
                'Embedding service connection refused',
                'Console command failed with exit code 1',
                'Database connection lost',
                'Memory limit exceeded',
                'Invalid RSS feed format',
                'HTTP 404 error on source',
            ];

            for ($j = 0; $j < min($errorsCount, 5); $j++) {
                $lastErrors[] = [
                    'service' => array_rand(array_flip(['parser', 'ai_service', 'embedding', 'console'])),
                    'message' => $errorMessages[array_rand($errorMessages)],
                    'timestamp' => $date->addHours(rand(0, 23))->toDateTimeString(),
                ];
            }

            // Compose daily data array
            $dayData = [
                'nodes_parsed' => ['count' => $nodesParsed],
                'nodes_duplicates' => ['count' => $nodesDuplicates],
                'nodes_sentiment_positive' => ['count' => $sentimentPositive],
                'nodes_sentiment_negative' => ['count' => $sentimentNegative],
                'nodes_sentiment_neutral' => ['count' => $sentimentNeutral],
                'nodes_emotion_anger' => ['count' => $emotionAnger],
                'nodes_emotion_joy' => ['count' => $emotionJoy],
                'nodes_emotion_fear' => ['count' => $emotionFear],
                'nodes_emotion_sadness' => ['count' => $emotionSadness],
                'nodes_emotion_disgust' => ['count' => $emotionDisgust],
                'nodes_emotion_surprise' => ['count' => $emotionSurprise],
                'nodes_emotion_neutral' => ['count' => $emotionNeutral],
                'errors' => [
                    'count' => $errorsCount,
                    'types' => $errorTypes,
                    'last_errors' => $lastErrors,
                ],
                'console_script_runs' => ['count' => $consoleRuns],
            ];

            // Save daily data
            \DB::table('stats_daily')->updateOrInsert(
                ['date' => $date->toDateString()],
                [
                    'data' => json_encode($dayData),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // 🔹 Aggregate monthly statistics
            if (! isset($monthlyData[$monthKey])) {
                $monthlyData[$monthKey] = [
                    'nodes_parsed' => 0,
                    'nodes_duplicates' => 0,
                    'sentiment_positive' => 0,
                    'sentiment_negative' => 0,
                    'sentiment_neutral' => 0,
                    'errors' => [
                        'count' => 0,
                        'types' => [
                            'parser' => 0,
                            'ai_service' => 0,
                            'embedding' => 0,
                            'console' => 0,
                        ],
                        'last_errors' => [],
                    ],
                    'console_script_runs' => 0,
                    'emotion_anger' => 0,
                    'emotion_joy' => 0,
                    'emotion_fear' => 0,
                    'emotion_sadness' => 0,
                    'emotion_disgust' => 0,
                    'emotion_surprise' => 0,
                    'emotion_neutral' => 0,
                ];
            }

            // Aggregate daily values into monthly
            $monthlyData[$monthKey]['nodes_parsed'] += $nodesParsed;
            $monthlyData[$monthKey]['nodes_duplicates'] += $nodesDuplicates;
            $monthlyData[$monthKey]['sentiment_positive'] += $sentimentPositive;
            $monthlyData[$monthKey]['sentiment_negative'] += $sentimentNegative;
            $monthlyData[$monthKey]['sentiment_neutral'] += $sentimentNeutral;

            $monthlyData[$monthKey]['emotion_anger'] += $emotionAnger;
            $monthlyData[$monthKey]['emotion_joy'] += $emotionJoy;
            $monthlyData[$monthKey]['emotion_fear'] += $emotionFear;
            $monthlyData[$monthKey]['emotion_sadness'] += $emotionSadness;
            $monthlyData[$monthKey]['emotion_disgust'] += $emotionDisgust;
            $monthlyData[$monthKey]['emotion_surprise'] += $emotionSurprise;
            $monthlyData[$monthKey]['emotion_neutral'] += $emotionNeutral;
            $monthlyData[$monthKey]['console_script_runs'] += $consoleRuns;

            $monthlyData[$monthKey]['errors']['count'] += $errorsCount;
            foreach ($errorTypes as $type => $val) {
                $monthlyData[$monthKey]['errors']['types'][$type] += $val;
            }

            // Keep last 10 errors for the month
            $monthlyData[$monthKey]['errors']['last_errors'] = array_slice(
                array_merge($monthlyData[$monthKey]['errors']['last_errors'], $lastErrors),
                -10
            );
        }

        // 🔹 Save monthly statistics
        $this->info("\nGenerating monthly statistics...");
        foreach ($monthlyData as $month => $data) {
            $this->line("  Processing month {$month}...");
            \DB::table('stats_monthly')->updateOrInsert(
                ['month' => $month],
                [
                    'data' => json_encode([
                        'nodes_parsed' => ['count' => $data['nodes_parsed']],
                        'nodes_duplicates' => ['count' => $data['nodes_duplicates']],
                        'nodes_sentiment_positive' => ['count' => $data['sentiment_positive']],
                        'nodes_sentiment_negative' => ['count' => $data['sentiment_negative']],
                        'nodes_sentiment_neutral' => ['count' => $data['sentiment_neutral']],
                        'errors' => $data['errors'],
                        'nodes_emotion_anger' => ['count' => $data['emotion_anger']],
                        'nodes_emotion_joy' => ['count' => $data['emotion_joy']],
                        'nodes_emotion_fear' => ['count' => $data['emotion_fear']],
                        'nodes_emotion_sadness' => ['count' => $data['emotion_sadness']],
                        'nodes_emotion_disgust' => ['count' => $data['emotion_disgust']],
                        'nodes_emotion_surprise' => ['count' => $data['emotion_surprise']],
                        'nodes_emotion_neutral' => ['count' => $data['emotion_neutral']],
                        'console_script_runs' => ['count' => $data['console_script_runs']],
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 🔹 Final summary
        $this->info("\n✓ Test data generated successfully");
        $this->info("\n=== Summary ===");
        $this->line("  Days generated: {$days}");
        $this->line('  Daily records: '.\DB::table('stats_daily')->count());
        $this->line('  Monthly records: '.\DB::table('stats_monthly')->count());

        $totalDuplicates = array_sum(array_column($monthlyData, 'nodes_duplicates'));
        $this->line("  Total duplicates simulated: {$totalDuplicates}");

        return self::SUCCESS;
    }
}
