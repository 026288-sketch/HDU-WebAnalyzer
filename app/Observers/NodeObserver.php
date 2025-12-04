<?php

namespace App\Observers;

use App\Models\Node;
use App\Services\Dashboard\DashboardCounterService;

/**
 * Observer for the Node model.
 *
 * Tracks creation, updates, and deletion of Node records to maintain
 * dashboard counters related to total nodes and parsed/missing content.
 */
class NodeObserver
{
    protected DashboardCounterService $counters;

    /**
     * NodeObserver constructor.
     *
     * @param  DashboardCounterService  $counters  Dashboard counter service
     */
    public function __construct(DashboardCounterService $counters)
    {
        $this->counters = $counters;
    }

    /**
     * Handle the "created" event for Node.
     *
     * Increments counters for total nodes and parsed/missing content
     * depending on whether the Node has content.
     */
    public function created(Node $node): void
    {
        // Increment total nodes counter
        $this->counters->increment('total_nodes');

        // Increment parsed or missing content counters
        if (! empty($node->content)) {
            $this->counters->increment('nodes_parsed');
        } else {
            $this->counters->increment('nodes_missing_content');
        }
    }

    /**
     * Handle the "updated" event for Node.
     *
     * Adjusts parsed/missing content counters if the content field changed.
     */
    public function updated(Node $node): void
    {
        $original = $node->getOriginal();

        // Update parsed vs missing content counters
        if (! empty($original['content']) && empty($node->content)) {
            $this->counters->increment('nodes_missing_content');
            $this->counters->decrement('nodes_parsed');
        } elseif (empty($original['content']) && ! empty($node->content)) {
            $this->counters->decrement('nodes_missing_content');
            $this->counters->increment('nodes_parsed');
        }
    }

    /**
     * Handle the "deleted" event for Node.
     *
     * Decrements counters for total nodes and parsed/missing content
     * depending on whether the Node had content.
     */
    public function deleted(Node $node): void
    {
        // Decrement total nodes counter
        $this->counters->decrement('total_nodes');

        // Decrement parsed or missing content counters
        if (! empty($node->content)) {
            $this->counters->decrement('nodes_parsed');
        } else {
            $this->counters->decrement('nodes_missing_content');
        }
    }
}
