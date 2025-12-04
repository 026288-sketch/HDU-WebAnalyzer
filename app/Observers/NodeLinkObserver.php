<?php

namespace App\Observers;

use App\Models\NodeLink;
use App\Services\Dashboard\DashboardCounterService;

/**
 * Observer for the NodeLink model.
 *
 * Tracks creation and updates of NodeLink records to identify duplicates.
 * When a link becomes marked as a duplicate, the duplicate counter in the
 * dashboard is incremented. Optional logging provides additional visibility
 * into duplicate detection events.
 */
class NodeLinkObserver
{
    public function __construct(
        private DashboardCounterService $counterService
    ) {}

    /**
     * Track updates of the NodeLink model.
     *
     * Triggered whenever a NodeLink record is updated. If the "is_duplicate"
     * field changes from false to true, the duplicate counter is incremented.
     */
    public function updated(NodeLink $nodeLink): void
    {
        // If the field "is_duplicate" changed and is now true
        if ($nodeLink->is_duplicate && $nodeLink->isDirty('is_duplicate')) {
            $this->counterService->increment(
                DashboardCounterService::KEY_NODES_DUPLICATES
            );
        }
    }

    /**
     * Optional: handle creation when a new NodeLink is already marked as a duplicate.
     *
     * Triggered when a new NodeLink record is created. If it is immediately marked
     * as a duplicate, the duplicate counter is incremented.
     */
    public function created(NodeLink $nodeLink): void
    {
        // If the record is created with "is_duplicate = true"
        if ($nodeLink->is_duplicate) {
            $this->counterService->increment(
                DashboardCounterService::KEY_NODES_DUPLICATES
            );
        }
    }
}
