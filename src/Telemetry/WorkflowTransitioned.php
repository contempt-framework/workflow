<?php

declare(strict_types=1);

namespace Contempt\Workflow\Telemetry;

final readonly class WorkflowTransitioned
{
    public function __construct(
        public string $workflow,
        public string $subjectId,
        public string $transition,
        public string $from,
        public string $to,
        public int $version,
    ) {}
}
