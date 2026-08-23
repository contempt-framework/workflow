<?php

declare(strict_types=1);

namespace Contempt\Workflow\Telemetry;

interface WorkflowObserver
{
    public function transitioned(WorkflowTransitioned $event): void;
}
