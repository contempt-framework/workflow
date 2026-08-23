<?php

declare(strict_types=1);

namespace Contempt\Workflow\Definition;

use Contempt\Workflow\State\WorkflowState;

/** @template TState of WorkflowState */
final readonly class WorkflowDefinition
{
    /** @var class-string<TState> */
    public string $stateClass;

    /** @var TState */
    public WorkflowState $initial;

    /** @var array<string, TransitionDefinition> */
    private array $transitions;

    /**
     * @param class-string<TState> $stateClass
     * @param TState $initial
     * @param list<TransitionDefinition> $transitions
     */
    public function __construct(WorkflowName $name, string $stateClass, WorkflowState $initial, array $transitions)
    {
        if (!$initial instanceof $stateClass || !$initial instanceof \UnitEnum) {
            throw new \InvalidArgumentException('Workflow initial state must be a member of its declared enum.');
        }

        $indexed = [];

        foreach ($transitions as $transition) {
            if (!$transition->to instanceof $stateClass) {
                throw new \InvalidArgumentException('Workflow target state belongs to a foreign enum.');
            }

            foreach ($transition->from as $from) {
                if (!$from instanceof $stateClass) {
                    throw new \InvalidArgumentException('Workflow source state belongs to a foreign enum.');
                }

                $key = self::key($transition->name, $from);

                if (isset($indexed[$key])) {
                    throw new \InvalidArgumentException('Workflow contains an ambiguous transition edge.');
                }

                $indexed[$key] = $transition;
            }
        }

        $this->name = $name;
        $this->stateClass = $stateClass;
        $this->initial = $initial;
        $this->transitions = $indexed;
    }

    public WorkflowName $name;

    public function transition(TransitionName $name, WorkflowState $from): ?TransitionDefinition
    {
        return $this->transitions[self::key($name, $from)] ?? null;
    }

    private static function key(TransitionName $name, WorkflowState $state): string
    {
        if (!$state instanceof \UnitEnum) {
            throw new \InvalidArgumentException('Workflow states must be PHP enums.');
        }

        return $name->value . "\0" . $state::class . '::' . $state->name;
    }
}
