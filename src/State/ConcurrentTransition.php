<?php

declare(strict_types=1);

namespace Contempt\Workflow\State;

use Contempt\Core\Exception\RuntimeException;

final class ConcurrentTransition extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Workflow state changed concurrently; reload it before retrying the transition.');
    }
}
