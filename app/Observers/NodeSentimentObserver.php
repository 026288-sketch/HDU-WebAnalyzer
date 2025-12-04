<?php

namespace App\Observers;

use App\Models\NodeSentiment;
use App\Services\Dashboard\DashboardCounterService;

/**
 * Observer for the NodeSentiment model.
 *
 * Tracks creation, updates, and deletion of NodeSentiment records to maintain
 * dashboard counters for sentiment and emotion values.
 *
 * Note: Sentiment and emotion mappings remain in Ukrainian.
 */
class NodeSentimentObserver
{
    protected DashboardCounterService $counters;

    // Mapping of sentiment values to counter keys
    private const SENTIMENT_MAP = [
        'позитивна' => DashboardCounterService::KEY_SENTIMENT_POSITIVE,
        'негативна' => DashboardCounterService::KEY_SENTIMENT_NEGATIVE,
        'нейтральна' => DashboardCounterService::KEY_SENTIMENT_NEUTRAL,
    ];

    // Mapping of emotion values to counter keys
    private const EMOTION_MAP = [
        'гнів' => DashboardCounterService::KEY_EMOTION_ANGER,
        'смуток' => DashboardCounterService::KEY_EMOTION_SADNESS,
        'відраза' => DashboardCounterService::KEY_EMOTION_DISGUST,
        'страх' => DashboardCounterService::KEY_EMOTION_FEAR,
        'радість' => DashboardCounterService::KEY_EMOTION_JOY,
        'здивування' => DashboardCounterService::KEY_EMOTION_SURPRISE,
        'нейтральна' => DashboardCounterService::KEY_EMOTION_NEUTRAL,
    ];

    /**
     * NodeSentimentObserver constructor.
     *
     * @param  DashboardCounterService  $counters  Dashboard counter service
     */
    public function __construct(DashboardCounterService $counters)
    {
        $this->counters = $counters;
    }

    /**
     * Handle the "created" event for NodeSentiment.
     *
     * Increments counters based on the sentiment and emotion of the new record.
     */
    public function created(NodeSentiment $nodeSentiment): void
    {
        $this->updateCounters($nodeSentiment, 'increment');
    }

    /**
     * Handle the "updated" event for NodeSentiment.
     *
     * Adjusts counters if sentiment or emotion values have changed.
     */
    public function updated(NodeSentiment $nodeSentiment): void
    {
        $originalSentiment = $nodeSentiment->getOriginal('sentiment');
        $originalEmotion = $nodeSentiment->getOriginal('emotion');

        $currentSentiment = $nodeSentiment->sentiment;
        $currentEmotion = $nodeSentiment->emotion;

        // If sentiment changed (including empty -> filled or vice versa)
        if ($originalSentiment !== $currentSentiment) {
            if (! empty($originalSentiment)) {
                $this->updateCountersValue($originalSentiment, 'sentiment', 'decrement');
            }
            if (! empty($currentSentiment)) {
                $this->updateCountersValue($currentSentiment, 'sentiment', 'increment');
            }
        }

        // If emotion changed (including empty -> filled or vice versa)
        if ($originalEmotion !== $currentEmotion) {
            if (! empty($originalEmotion)) {
                $this->updateCountersValue($originalEmotion, 'emotion', 'decrement');
            }
            if (! empty($currentEmotion)) {
                $this->updateCountersValue($currentEmotion, 'emotion', 'increment');
            }
        }
    }

    /**
     * Handle the "deleted" event for NodeSentiment.
     *
     * Decrements counters based on the sentiment and emotion of the deleted record.
     */
    public function deleted(NodeSentiment $nodeSentiment): void
    {
        $this->updateCounters($nodeSentiment, 'decrement');
    }

    /**
     * Update both sentiment and emotion counters.
     *
     * @param  string  $action  "increment" or "decrement"
     */
    protected function updateCounters(NodeSentiment $nodeSentiment, string $action): void
    {
        $this->updateCountersValue($nodeSentiment->sentiment, 'sentiment', $action);
        $this->updateCountersValue($nodeSentiment->emotion, 'emotion', $action);
    }

    /**
     * Update a single counter based on value and type.
     *
     * @param  string|null  $value  Sentiment or emotion value
     * @param  string  $type  "sentiment" or "emotion"
     * @param  string  $action  "increment" or "decrement"
     */
    protected function updateCountersValue(?string $value, string $type, string $action): void
    {
        if (empty($value)) {
            return;
        }

        $map = match ($type) {
            'sentiment' => self::SENTIMENT_MAP,
            'emotion' => self::EMOTION_MAP,
            default => [],
        };

        $key = $map[$value] ?? null;

        if ($key) {
            if ($action === 'increment') {
                $this->counters->increment($key);
            } else {
                $this->counters->decrement($key);
            }
        }
    }
}
