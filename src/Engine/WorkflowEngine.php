<?php

declare(strict_types=1);

namespace Contempt\Workflow\Engine;

use Contempt\Contracts\Transaction\TransactionManager;
use Contempt\Workflow\Definition\TransitionName;
use Contempt\Workflow\Definition\WorkflowDefinition;
use Contempt\Workflow\State\WorkflowState;
use Contempt\Workflow\State\WorkflowStateRecord;
use Contempt\Workflow\State\WorkflowStateStore;
use Contempt\Workflow\Subject\WorkflowSubject;
use Contempt\Workflow\Telemetry\NullWorkflowObserver;
use Contempt\Workflow\Telemetry\WorkflowObserver;
use Contempt\Workflow\Telemetry\WorkflowTransitioned;

final readonly class WorkflowEngine
{
    public function __construct(
        private WorkflowStateStore $store,
        private WorkflowObserver $observer = new NullWorkflowObserver(),
        private ?TransactionManager $transactions = null,
    ) {}

    /**
     * @template TState of WorkflowState
     * @param WorkflowDefinition<TState> $definition
     */
    public function state(WorkflowDefinition $definition, WorkflowSubject $subject): WorkflowStateRecord
    {
        $subjectId = self::subjectId($subject);

        return $this->store->load($definition->name, $subjectId)
            ?? $this->store->initialize($definition->name, $subjectId, $definition->initial);
    }

    /**
     * @template TState of WorkflowState
     * @param WorkflowDefinition<TState> $definition
     * @return TransitionResult<TState>
     */
    public function transition(WorkflowDefinition $definition, WorkflowSubject $subject, TransitionName $name): TransitionResult
    {
        $operation = fn(): TransitionResult => $this->transitionAtomically($definition, $subject, $name);
        $result = $this->transactions === null ? $operation() : $this->transactions->transactional($operation);

        try {
            $this->observer->transitioned(new WorkflowTransitioned(
                $definition->name->value,
                self::subjectId($subject),
                $name->value,
                self::stateValue($result->from),
                self::stateValue($result->current),
                $result->version,
            ));
        } catch (\Throwable) {
        }

        return $result;
    }

    /**
     * @template TState of WorkflowState
     * @param WorkflowDefinition<TState> $definition
     * @return TransitionResult<TState>
     */
    private function transitionAtomically(WorkflowDefinition $definition, WorkflowSubject $subject, TransitionName $name): TransitionResult
    {
        $subjectId = self::subjectId($subject);
        $record = $this->store->load($definition->name, $subjectId)
            ?? $this->store->initialize($definition->name, $subjectId, $definition->initial);
        $transition = $definition->transition($name, $record->state) ?? throw new UnknownTransition();

        foreach ($transition->guards as $guard) {
            if (!$guard->allows($subject)) {
                throw new TransitionDenied();
            }
        }

        $updated = $this->store->compareAndSet(
            $definition->name,
            $subjectId,
            $record->version,
            $transition->to,
        );

        if (!$record->state instanceof $definition->stateClass || !$updated->state instanceof $definition->stateClass) {
            throw new \LogicException('Workflow state store returned a state from a foreign enum.');
        }

        return new TransitionResult($record->state, $updated->state, $updated->version);
    }

    private static function subjectId(WorkflowSubject $subject): string
    {
        $id = $subject->workflowSubjectId();

        if ($id === '' || \strlen($id) > 255 || preg_match('/[\x00-\x1F\x7F]/', $id) === 1) {
            throw new \InvalidArgumentException('Workflow subject id must contain 1-255 printable bytes.');
        }

        return $id;
    }

    private static function stateValue(WorkflowState $state): string
    {
        if (!$state instanceof \BackedEnum) {
            return $state instanceof \UnitEnum ? $state->name : throw new \LogicException('Workflow state is not an enum.');
        }

        return (string) $state->value;
    }
}
