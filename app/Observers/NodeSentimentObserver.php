<?php

namespace App\Observers;

use App\Models\NodeSentiment;
use App\Services\Dashboard\DashboardCounterService;

class NodeSentimentObserver
{
    protected DashboardCounterService $counters;

    // Маппинг значений сентимента на ключи счетчиков
    private const SENTIMENT_MAP = [
        'позитивна' => DashboardCounterService::KEY_SENTIMENT_POSITIVE,
        'негативна' => DashboardCounterService::KEY_SENTIMENT_NEGATIVE,
        'нейтральна' => DashboardCounterService::KEY_SENTIMENT_NEUTRAL,
    ];

    // Маппинг значений эмоций на ключи счетчиков
    private const EMOTION_MAP = [
        'гнів' => DashboardCounterService::KEY_EMOTION_ANGER,
        'смуток' => DashboardCounterService::KEY_EMOTION_SADNESS,
        'відраза' => DashboardCounterService::KEY_EMOTION_DISGUST,
        'страх' => DashboardCounterService::KEY_EMOTION_FEAR,
        'радість' => DashboardCounterService::KEY_EMOTION_JOY,
        'здивування' => DashboardCounterService::KEY_EMOTION_SURPRISE,
        'нейтральна' => DashboardCounterService::KEY_EMOTION_NEUTRAL,
    ];

    public function __construct(DashboardCounterService $counters)
    {
        $this->counters = $counters;
    }

    public function created(NodeSentiment $nodeSentiment): void
    {
        $this->updateCounters($nodeSentiment, 'increment');
    }

    public function updated(NodeSentiment $nodeSentiment): void
    {
        $originalSentiment = $nodeSentiment->getOriginal('sentiment');
        $originalEmotion = $nodeSentiment->getOriginal('emotion');

        $currentSentiment = $nodeSentiment->sentiment;
        $currentEmotion = $nodeSentiment->emotion;

        // Если сентимент изменился (включая случаи пустое -> заполненное и наоборот)
        if ($originalSentiment !== $currentSentiment) {
            if (! empty($originalSentiment)) {
                $this->updateCountersValue($originalSentiment, 'sentiment', 'decrement');
            }
            if (! empty($currentSentiment)) {
                $this->updateCountersValue($currentSentiment, 'sentiment', 'increment');
            }
        }

        // Если эмоция изменилась (включая случаи пустое -> заполненное и наоборот)
        if ($originalEmotion !== $currentEmotion) {
            if (! empty($originalEmotion)) {
                $this->updateCountersValue($originalEmotion, 'emotion', 'decrement');
            }
            if (! empty($currentEmotion)) {
                $this->updateCountersValue($currentEmotion, 'emotion', 'increment');
            }
        }
    }

    public function deleted(NodeSentiment $nodeSentiment): void
    {
        $this->updateCounters($nodeSentiment, 'decrement');
    }

    protected function updateCounters(NodeSentiment $nodeSentiment, string $action): void
    {
        $this->updateCountersValue($nodeSentiment->sentiment, 'sentiment', $action);
        $this->updateCountersValue($nodeSentiment->emotion, 'emotion', $action);
    }

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
