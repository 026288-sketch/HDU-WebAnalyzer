<?php

namespace App\Observers;

use App\Models\NodeLink;
use App\Services\Dashboard\DashboardCounterService;

class NodeLinkObserver
{
    public function __construct(
        private DashboardCounterService $counterService
    ) {}

    /**
     * Отслеживаем обновление NodeLink
     */
    public function updated(NodeLink $nodeLink): void
    {
        // Если поле is_duplicate изменилось с false на true
        if ($nodeLink->is_duplicate && $nodeLink->isDirty('is_duplicate')) {
            $this->counterService->increment(
                DashboardCounterService::KEY_NODES_DUPLICATES
            );

            // Опционально: логирование
            \Log::info('Duplicate detected', [
                'node_link_id' => $nodeLink->id,
                'url' => $nodeLink->url,
                'duplicate_of' => $nodeLink->duplicate_of,
            ]);
        }
    }

    /**
     * Опционально: при создании сразу как дубликат
     */
    public function created(NodeLink $nodeLink): void
    {
        // Если создается сразу как дубликат
        if ($nodeLink->is_duplicate) {
            $this->counterService->increment(
                DashboardCounterService::KEY_NODES_DUPLICATES
            );
        }
    }
}
