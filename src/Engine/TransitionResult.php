<?php

declare(strict_types=1);

namespace Contempt\Workflow\Engine;

use Contempt\Workflow\State\WorkflowState;

/** @template TState of WorkflowState */
final readonly class TransitionResult
{
    /** @param TState $from @param TState $current */
    public function __construct(
        public WorkflowState $from,
        public WorkflowState $current,
        public int $version,
    ) {}
}
