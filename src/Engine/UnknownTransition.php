<?php

declare(strict_types=1);

namespace Contempt\Workflow\Engine;

use Contempt\Core\Exception\RuntimeException;

final class UnknownTransition extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The requested transition is not valid from the current workflow state.');
    }
}
