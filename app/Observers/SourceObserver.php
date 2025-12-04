<?php

namespace App\Observers;

use App\Models\Source;
use App\Services\Dashboard\DashboardCounterService;

/**
 * Observer for the Source model.
 *
 * Tracks creation, updates, and deletion of Source records to maintain
 * the total sources count in the dashboard.
 */
class SourceObserver
{
    protected DashboardCounterService $counters;

    /**
     * SourceObserver constructor.
     *
     * @param  DashboardCounterService  $counters  Dashboard counter service
     */
    public function __construct(DashboardCounterService $counters)
    {
        $this->counters = $counters;
    }

    /**
     * Handle the "created" event for Source.
     *
     * Updates the total sources count in the dashboard.
     */
    public function created(Source $source): void
    {
        $this->counters->updateTotalSources();
    }

    /**
     * Handle the "updated" event for Source.
     *
     * Updates the total sources count in the dashboard.
     */
    public function updated(Source $source): void
    {
        $this->counters->updateTotalSources();
    }

    /**
     * Handle the "deleted" event for Source.
     *
     * Updates the total sources count in the dashboard.
     */
    public function deleted(Source $source): void
    {
        $this->counters->updateTotalSources();
    }
}
