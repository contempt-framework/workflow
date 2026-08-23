<?php

declare(strict_types=1);

namespace Contempt\Workflow\Engine;

use Contempt\Core\Exception\RuntimeException;

final class TransitionDenied extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('A workflow guard denied the requested transition.');
    }
}
