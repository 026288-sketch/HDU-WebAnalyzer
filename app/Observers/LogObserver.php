<?php

namespace App\Observers;

use App\Models\Log;
use App\Services\Dashboard\DashboardCounterService;

class LogObserver
{
    protected DashboardCounterService $counters;

    public function __construct(DashboardCounterService $counters)
    {
        $this->counters = $counters;
    }

    public function created(Log $log): void
    {
        $this->counters->updateErrorsOnLogCreated($log);
    }
}


