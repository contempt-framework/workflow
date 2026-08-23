<?php

declare(strict_types=1);

namespace Contempt\Workflow;

use Contempt\Compiler\Extension\PackageExtension;

final readonly class WorkflowExtension extends PackageExtension
{
    protected function package(): string
    {
        return 'contempt/workflow';
    }
}
