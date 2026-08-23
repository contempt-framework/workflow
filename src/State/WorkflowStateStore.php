<?php

declare(strict_types=1);

namespace Contempt\Workflow\State;

use Contempt\Workflow\Definition\WorkflowName;

interface WorkflowStateStore
{
    public function load(WorkflowName $workflow, string $subjectId): ?WorkflowStateRecord;

    /** Must atomically return the existing record if another caller initialized it first. */
    public function initialize(WorkflowName $workflow, string $subjectId, WorkflowState $initial): WorkflowStateRecord;

    /** Must use an atomic compare-and-set on expectedVersion. */
    public function compareAndSet(
        WorkflowName $workflow,
        string $subjectId,
        int $expectedVersion,
        WorkflowState $next,
    ): WorkflowStateRecord;
}
