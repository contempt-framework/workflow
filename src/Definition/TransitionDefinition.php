<?php

declare(strict_types=1);

namespace Contempt\Workflow\Definition;

use Contempt\Workflow\Guard\TransitionGuard;
use Contempt\Workflow\State\WorkflowState;

final readonly class TransitionDefinition
{
    /** @var list<WorkflowState> */
    public array $from;

    /** @var list<TransitionGuard> */
    public array $guards;

    /**
     * @param list<WorkflowState> $from
     * @param list<TransitionGuard> $guards
     */
    public function __construct(
        public TransitionName $name,
        array $from,
        public WorkflowState $to,
        array $guards = [],
    ) {
        if ($from === []) {
            throw new \InvalidArgumentException('A workflow transition must have at least one source state.');
        }

        $states = [];

        foreach ($from as $state) {
            $key = self::stateKey($state);

            if (isset($states[$key])) {
                throw new \InvalidArgumentException('A workflow transition repeats a source state.');
            }

            $states[$key] = $state;
        }

        $this->from = array_values($states);
        $this->guards = array_values($guards);
    }

    public function supports(WorkflowState $state): bool
    {
        foreach ($this->from as $candidate) {
            if ($candidate === $state) {
                return true;
            }
        }

        return false;
    }

    private static function stateKey(WorkflowState $state): string
    {
        if (!$state instanceof \UnitEnum) {
            throw new \InvalidArgumentException('Workflow states must be PHP enums.');
        }

        return $state::class . '::' . $state->name;
    }
}
