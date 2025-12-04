<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardCounterService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Class DashboardController
 *
 * Manages the dashboard overview and chart data for nodes, sentiments, emotions, errors, and tags.
 */
class DashboardController extends Controller
{
    /**
     * Display the main dashboard page with summary statistics.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Retrieve all counters from the DB
        $counters = DB::table('dashboard_counters')
            ->get()
            ->keyBy('key')
            ->map(fn ($item) => json_decode($item->value, true));

        // Overview totals
        $totalNodes = $counters[DashboardCounterService::KEY_TOTAL_NODES]['count'] ?? 0;
        $totalTags = $counters[DashboardCounterService::KEY_TOTAL_TAGS]['count'] ?? 0;

        $sources = $counters[DashboardCounterService::KEY_TOTAL_SOURCES] ?? [];
        $totalSources = $sources['count'] ?? 0;
        $rssSources = $sources['rss'] ?? 0;
        $fullRssSources = $sources['full_rss'] ?? 0;
        $browserSources = $sources['browser_required'] ?? 0;

        $nodesParsed = $counters[DashboardCounterService::KEY_NODES_PARSED]['count'] ?? 0;
        $nodesDuplicates = $counters[DashboardCounterService::KEY_NODES_DUPLICATES]['count'] ?? 0;
        $consoleCommands = $counters[DashboardCounterService::KEY_CONSOLE_SCRIPT_RUNS]['count'] ?? 0;

        // Emotions
        $emotions = [
            'anger' => $counters[DashboardCounterService::KEY_EMOTION_ANGER]['count'] ?? 0,
            'sadness' => $counters[DashboardCounterService::KEY_EMOTION_SADNESS]['count'] ?? 0,
            'disgust' => $counters[DashboardCounterService::KEY_EMOTION_DISGUST]['count'] ?? 0,
            'fear' => $counters[DashboardCounterService::KEY_EMOTION_FEAR]['count'] ?? 0,
            'joy' => $counters[DashboardCounterService::KEY_EMOTION_JOY]['count'] ?? 0,
            'surprise' => $counters[DashboardCounterService::KEY_EMOTION_SURPRISE]['count'] ?? 0,
            'neutral' => $counters[DashboardCounterService::KEY_EMOTION_NEUTRAL]['count'] ?? 0,
        ];

        // Sentiment
        $sentiment = [
            'positive' => $counters[DashboardCounterService::KEY_SENTIMENT_POSITIVE]['count'] ?? 0,
            'negative' => $counters[DashboardCounterService::KEY_SENTIMENT_NEGATIVE]['count'] ?? 0,
            'neutral' => $counters[DashboardCounterService::KEY_SENTIMENT_NEUTRAL]['count'] ?? 0,
        ];

        // Errors
        $errors = $counters[DashboardCounterService::KEY_ERRORS] ?? [];
        $totalErrors = $errors['count'] ?? 0;
        $errorTypes = $errors['types'] ?? [];
        $lastErrors = $errors['last_errors'] ?? [];

        // Get chart data for last 30 days
        $chartData = $this->getChartData(30);

        // Popular tags
        $popularTags = DB::table('tags')
            ->join('node_tag', 'tags.id', '=', 'node_tag.tag_id')
            ->select('tags.name', 'tags.slug', DB::raw('COUNT(node_tag.tag_id) as usage_count'))
            ->groupBy('tags.name', 'tags.slug')
            ->orderByDesc('usage_count')
            ->limit(50)
            ->get();
        $top10Tags = $popularTags->take(10);

        // Count unparsed links
        $unparsedLinks = DB::table('node_links')
            ->where('parsed', 0)
            ->count();

        return view('dashboard', compact(
            'totalNodes',
            'totalTags',
            'totalSources',
            'rssSources',
            'browserSources',
            'fullRssSources',
            'nodesParsed',
            'nodesDuplicates',
            'totalErrors',
            'emotions',
            'sentiment',
            'errorTypes',
            'lastErrors',
            'chartData',
            'popularTags',
            'top10Tags',
            'unparsedLinks',
            'consoleCommands'
        ));
    }

    /**
     * Return chart data via AJAX request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChartDataAjax(Request $request)
    {
        $days = $request->get('days');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $group = $request->get('group', 'daily'); // daily or monthly

        if ($dateFrom && $dateTo) {
            return response()->json($this->getChartDataByDateRange($dateFrom, $dateTo, $group));
        }

        return response()->json($this->getChartData($days ?? 30, $group));
    }

    /**
     * Get chart data for the last N days.
     */
    private function getChartData(int $days, string $group = 'daily'): array
    {
        $endDate = Carbon::today();
        $startDate = Carbon::today()->subDays($days);

        return $this->getChartDataByDateRange($startDate->toDateString(), $endDate->toDateString(), $group);
    }

    /**
     * Get chart data by a custom date range.
     */
    private function getChartDataByDateRange(string $startDate, string $endDate, string $group = 'daily'): array
    {
        if ($group === 'monthly') {
            return $this->getMonthlyChartData($startDate, $endDate);
        }

        $dailyStats = DB::table('stats_daily')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get();

        $labels = [];
        $nodesParsed = [];
        $sentimentPositive = [];
        $sentimentNegative = [];
        $errors = [];
        $duplicates = [];
        $consoleCommands = [];
        $emotions = [
            'anger' => [],
            'sadness' => [],
            'disgust' => [],
            'fear' => [],
            'joy' => [],
            'surprise' => [],
            'neutral' => [],
        ];

        foreach ($dailyStats as $day) {
            $data = json_decode($day->data, true);

            $labels[] = Carbon::parse($day->date)->format('d.m');
            $nodesParsed[] = $data['nodes_parsed']['count'] ?? 0;
            $sentimentPositive[] = $data['nodes_sentiment_positive']['count'] ?? 0;
            $sentimentNegative[] = $data['nodes_sentiment_negative']['count'] ?? 0;
            $errors[] = $data['errors']['count'] ?? 0;
            $consoleCommands[] = $data['console_script_runs']['count'] ?? 0;
            $duplicates[] = $data['nodes_duplicates']['count'] ?? 0;

            foreach ($emotions as $key => $_) {
                $emotions[$key][] = $data['nodes_emotion_'.$key]['count'] ?? 0;
            }
        }

        return [
            'labels' => $labels,
            'nodesParsed' => $nodesParsed,
            'duplicates' => $duplicates,
            'consoleCommands' => $consoleCommands,
            'sentimentPositive' => $sentimentPositive,
            'sentimentNegative' => $sentimentNegative,
            'emotions' => $emotions,
            'errors' => $errors,
        ];
    }

    /**
     * Get monthly chart data for a given date range.
     */
    private function getMonthlyChartData(string $startDate, string $endDate): array
    {
        $startMonth = Carbon::parse($startDate)->format('Y-m');
        $endMonth = Carbon::parse($endDate)->format('Y-m');

        $monthlyStats = DB::table('stats_monthly')
            ->whereBetween('month', [$startMonth, $endMonth])
            ->orderBy('month', 'asc')
            ->get();

        $labels = [];
        $nodesParsed = [];
        $sentimentPositive = [];
        $sentimentNegative = [];
        $errors = [];
        $duplicates = [];
        $consoleCommands = [];
        $emotions = [
            'anger' => [],
            'sadness' => [],
            'disgust' => [],
            'fear' => [],
            'joy' => [],
            'surprise' => [],
            'neutral' => [],
        ];

        foreach ($monthlyStats as $month) {
            $data = json_decode($month->data, true);

            $labels[] = Carbon::parse($month->month.'-01')->format('M Y');
            $nodesParsed[] = $data['nodes_parsed']['count'] ?? 0;
            $sentimentPositive[] = $data['nodes_sentiment_positive']['count'] ?? 0;
            $sentimentNegative[] = $data['nodes_sentiment_negative']['count'] ?? 0;
            $errors[] = $data['errors']['count'] ?? 0;
            $consoleCommands[] = $data['console_script_runs']['count'] ?? 0;
            $duplicates[] = $data['nodes_duplicates']['count'] ?? 0;

            foreach ($emotions as $key => $_) {
                $emotions[$key][] = $data['nodes_emotion_'.$key]['count'] ?? 0;
            }
        }

        return [
            'labels' => $labels,
            'nodesParsed' => $nodesParsed,
            'duplicates' => $duplicates,
            'consoleCommands' => $consoleCommands,
            'sentimentPositive' => $sentimentPositive,
            'sentimentNegative' => $sentimentNegative,
            'errors' => $errors,
            'emotions' => $emotions,
        ];
    }
}
