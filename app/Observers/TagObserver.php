<?php

namespace App\Observers;

use App\Models\Tag;
use App\Services\Dashboard\DashboardCounterService;

/**
 * Observer for the Tag model.
 *
 * Tracks creation and deletion of Tag records to maintain
 * the total tags count in the dashboard.
 */
class TagObserver
{
    protected DashboardCounterService $counters;

    /**
     * TagObserver constructor.
     *
     * @param  DashboardCounterService  $counters  Dashboard counter service
     */
    public function __construct(DashboardCounterService $counters)
    {
        $this->counters = $counters;
    }

    /**
     * Handle the "created" event for Tag.
     *
     * Updates the total tags count in the dashboard.
     */
    public function created(Tag $tag): void
    {
        $this->counters->updateTotalTags();
    }

    /**
     * Handle the "deleted" event for Tag.
     *
     * Updates the total tags count in the dashboard.
     */
    public function deleted(Tag $tag): void
    {
        $this->counters->updateTotalTags();
    }
}
