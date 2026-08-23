<?php

declare(strict_types=1);

namespace Contempt\Workflow\Telemetry;

final readonly class NullWorkflowObserver implements WorkflowObserver
{
    public function transitioned(WorkflowTransitioned $event): void {}
}
