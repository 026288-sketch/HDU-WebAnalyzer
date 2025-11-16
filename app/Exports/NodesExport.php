<?php

namespace App\Exports;

use App\Models\Node;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Class NodesExport
 *
 * This export class is used to generate Excel files for nodes/articles.
 * It supports dynamic selection of fields including:
 * - Core node fields: timestamp, title, summary, content, url, image, hash
 * - Aggregated fields: sentiment, emotion
 * - Tags associated with the node
 *
 * Implements:
 * - FromQuery: build a query for large datasets
 * - WithChunkReading: process data in chunks to reduce memory usage
 * - WithHeadings: define custom column headings
 * - WithMapping: transform node data into row arrays
 */
class NodesExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    /**
     * Fields to export. Can be customized via constructor.
     *
     * @var array
     */
    protected $fields;

    /**
     * Constructor.
     *
     * @param  array  $fields  Optional fields to include in export.
     */
    public function __construct(array $fields = [])
    {
        $this->fields = ! empty($fields) ? $fields : [
            'timestamp', 'title', 'summary', 'content', 'url', 'image', 'hash',
            'sentiment', 'emotion', 'tags',
        ];
    }

    /**
     * Build the query for exporting nodes.
     *
     * Dynamically includes requested fields and handles joins for:
     * - Node sentiments (aggregated)
     * - Tags (concatenated)
     */
    public function query(): Builder
    {
        $select = ['nodes.id'];
        $nodeFields = ['timestamp', 'title', 'summary', 'content', 'url', 'image', 'hash'];

        // Add selected node fields or aggregated fields
        foreach ($this->fields as $field) {
            if (in_array($field, $nodeFields)) {
                $select[] = "nodes.$field";
            }
            if ($field === 'sentiment') {
                $select[] = DB::raw('MAX(node_sentiments.sentiment) as sentiment');
            }
            if ($field === 'emotion') {
                $select[] = DB::raw('MAX(node_sentiments.emotion) as emotion');
            }
            if ($field === 'tags') {
                $select[] = DB::raw('GROUP_CONCAT(DISTINCT tags.name SEPARATOR ", ") as tags');
            }
        }

        $query = Node::query()
            ->select($select)
            ->leftJoin('node_sentiments', 'node_sentiments.node_id', '=', 'nodes.id');

        // Join tags if needed
        if (in_array('tags', $this->fields)) {
            $query->leftJoin('node_tag', 'node_tag.node_id', '=', 'nodes.id')
                ->leftJoin('tags', 'tags.id', '=', 'node_tag.tag_id');
        }

        return $query->groupBy('nodes.id')
            ->orderBy('nodes.id', 'asc');
    }

    /**
     * Define column headings for Excel export.
     *
     * Maps internal field names to human-readable column names.
     */
    public function headings(): array
    {
        $headingMap = [
            'timestamp' => 'Date/Time',
            'title' => 'Title',
            'summary' => 'Summary',
            'content' => 'Content',
            'url' => 'URL',
            'image' => 'Image',
            'hash' => 'Hash',
            'sentiment' => 'Sentiment',
            'emotion' => 'Emotion',
            'tags' => 'Tags',
        ];

        $headings = [];
        foreach ($this->fields as $field) {
            if (isset($headingMap[$field])) {
                $headings[] = $headingMap[$field];
            }
        }

        return $headings;
    }

    /**
     * Map node model to a single row for export.
     *
     * Cleans text fields and ensures proper formatting for Excel.
     *
     * @param  \App\Models\Node  $node
     */
    public function map($node): array
    {
        $row = [];

        foreach ($this->fields as $field) {
            switch ($field) {
                case 'timestamp':
                    $row[] = (string) $node->timestamp;
                    break;
                case 'title':
                    $row[] = $this->cleanText($node->title);
                    break;
                case 'summary':
                    $row[] = $this->cleanText($node->summary);
                    break;
                case 'content':
                    $row[] = $this->cleanText($node->content);
                    break;
                case 'url':
                    $row[] = $node->url;
                    break;
                case 'image':
                    $row[] = $node->image;
                    break;
                case 'hash':
                    $row[] = $node->hash;
                    break;
                case 'sentiment':
                    $row[] = $node->sentiment ?? '';
                    break;
                case 'emotion':
                    $row[] = $node->emotion ?? '';
                    break;
                case 'tags':
                    $row[] = $node->tags ?? '';
                    break;
            }
        }

        return $row;
    }

    /**
     * Clean and normalize text fields for export.
     */
    private function cleanText(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        $text = strip_tags($text); // remove HTML tags
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'); // decode HTML entities

        return trim(preg_replace('/\s+/u', ' ', $text)); // normalize whitespace
    }

    /**
     * Set chunk size for reading large datasets.
     *
     * Reduces memory usage for very large exports.
     */
    public function chunkSize(): int
    {
        return 1000;
    }
}
