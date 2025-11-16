<?php

namespace App\Observers;

use App\Models\Source;
use App\Services\Dashboard\DashboardCounterService;

class SourceObserver
{
    protected DashboardCounterService $counters;

    public function __construct(DashboardCounterService $counters)
    {
        $this->counters = $counters;
    }

    public function created(Source $source): void
    {
        $this->counters->updateTotalSources();
    }

    public function updated(Source $source): void
    {
        $this->counters->updateTotalSources();
    }

    public function deleted(Source $source): void
    {
        $this->counters->updateTotalSources();
    }
}
