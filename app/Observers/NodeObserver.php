<?php

namespace App\Observers;

use App\Models\Node;
use App\Services\Dashboard\DashboardCounterService;

class NodeObserver
{
    protected DashboardCounterService $counters;

    public function __construct(DashboardCounterService $counters)
    {
        $this->counters = $counters;
    }

    public function created(Node $node): void
    {
        // total nodes
        $this->counters->increment('total_nodes');

        // parsed vs missing content
        if (! empty($node->content)) {
            $this->counters->increment('nodes_parsed');
        } else {
            $this->counters->increment('nodes_missing_content');
        }
    }

    public function updated(Node $node): void
    {
        $original = $node->getOriginal();

        // content: поддерживаем nodes_missing_content и nodes_parsed
        if (! empty($original['content']) && empty($node->content)) {
            $this->counters->increment('nodes_missing_content');
            $this->counters->decrement('nodes_parsed');
        } elseif (empty($original['content']) && ! empty($node->content)) {
            $this->counters->decrement('nodes_missing_content');
            $this->counters->increment('nodes_parsed');
        }
    }

    public function deleted(Node $node): void
    {
        // total nodes
        $this->counters->decrement('total_nodes');

        // parsed vs missing content
        if (! empty($node->content)) {
            $this->counters->decrement('nodes_parsed');
        } else {
            $this->counters->decrement('nodes_missing_content');
        }
    }
}
