<?php

declare(strict_types=1);

namespace Contempt\Workflow\State;

final readonly class WorkflowStateRecord
{
    public function __construct(public WorkflowState $state, public int $version)
    {
        if ($version < 0) {
            throw new \InvalidArgumentException('Workflow state version must not be negative.');
        }
    }
}
