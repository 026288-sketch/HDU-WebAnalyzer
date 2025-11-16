<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Class StatsExport
 *
 * Export daily or monthly statistics to Excel.
 * Supports configurable date range and grouping ('daily' or 'monthly').
 *
 * Implements:
 * - FromQuery: returns a query builder for large datasets
 * - WithChunkReading: processes data in chunks to reduce memory usage
 * - WithHeadings: defines column headers
 * - WithMapping: transforms DB rows into exportable arrays
 */
class StatsExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    /**
     * Start date for export.
     *
     * @var string
     */
    protected $startDate;

    /**
     * End date for export.
     *
     * @var string
     */
    protected $endDate;

    /**
     * Grouping type: 'daily' or 'monthly'.
     *
     * @var string
     */
    protected $group;

    /**
     * Constructor.
     *
     * @param  string  $startDate  Start date (YYYY-MM-DD)
     * @param  string  $endDate  End date (YYYY-MM-DD)
     * @param  string  $group  Grouping type: 'daily' or 'monthly'
     */
    public function __construct($startDate, $endDate, $group = 'daily')
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->group = $group;
    }

    /**
     * Build the query for export.
     *
     * Uses stats_daily or stats_monthly depending on the group.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function query()
    {
        if ($this->group === 'monthly') {
            $startMonth = Carbon::parse($this->startDate)->format('Y-m');
            $endMonth = Carbon::parse($this->endDate)->format('Y-m');

            return DB::table('stats_monthly')
                ->whereBetween('month', [$startMonth, $endMonth])
                ->orderBy('month', 'asc');
        }

        return DB::table('stats_daily')
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->orderBy('date', 'asc');
    }

    /**
     * Define Excel column headers.
     */
    public function headings(): array
    {
        return [
            'Date/Month',
            'Articles Processed',
            'Positive Sentiment',
            'Negative Sentiment',
            'Neutral Sentiment',
            'Anger',
            'Joy',
            'Fear',
            'Sadness',
            'Disgust',
            'Surprise',
            'Neutral Emotion',
            'Duplicates',
            'Console Scripts Run',
            'Errors',
        ];
    }

    /**
     * Map a database row to an Excel row.
     *
     * Decodes JSON data and formats dates appropriately.
     *
     * @param  object  $row
     */
    public function map($row): array
    {
        $data = json_decode($row->data, true);

        $date = isset($row->date)
            ? Carbon::parse($row->date)->format('d.m.Y')
            : Carbon::parse($row->month.'-01')->format('m.Y');

        return [
            $date,
            $data['nodes_parsed']['count'] ?? 0,
            $data['nodes_sentiment_positive']['count'] ?? 0,
            $data['nodes_sentiment_negative']['count'] ?? 0,
            $data['nodes_sentiment_neutral']['count'] ?? 0,
            $data['nodes_emotion_anger']['count'] ?? 0,
            $data['nodes_emotion_joy']['count'] ?? 0,
            $data['nodes_emotion_fear']['count'] ?? 0,
            $data['nodes_emotion_sadness']['count'] ?? 0,
            $data['nodes_emotion_disgust']['count'] ?? 0,
            $data['nodes_emotion_surprise']['count'] ?? 0,
            $data['nodes_emotion_neutral']['count'] ?? 0,
            $data['nodes_duplicates']['count'] ?? 0,
            $data['console_script_runs']['count'] ?? 0,
            $data['errors']['count'] ?? 0,
        ];
    }

    /**
     * Set chunk size for reading large datasets.
     *
     * Reduces memory usage for exports with many records.
     */
    public function chunkSize(): int
    {
        return 500; // Adjust as needed based on memory
    }
}
