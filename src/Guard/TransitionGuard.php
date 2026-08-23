<?php

declare(strict_types=1);

namespace Contempt\Workflow\Guard;

use Contempt\Workflow\Subject\WorkflowSubject;

interface TransitionGuard
{
    public function allows(WorkflowSubject $subject): bool;
}
