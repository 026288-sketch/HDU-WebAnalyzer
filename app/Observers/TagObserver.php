<?php

namespace App\Observers;

use App\Models\Tag;
use App\Services\Dashboard\DashboardCounterService;

class TagObserver
{
    protected DashboardCounterService $counters;

    public function __construct(DashboardCounterService $counters)
    {
        $this->counters = $counters;
    }

    public function created(Tag $tag): void
    {
        $this->counters->updateTotalTags();
    }

    public function deleted(Tag $tag): void
    {
        $this->counters->updateTotalTags();
    }
}
