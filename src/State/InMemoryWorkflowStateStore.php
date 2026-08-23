<?php

declare(strict_types=1);

namespace Contempt\Workflow\State;

use Contempt\Workflow\Definition\WorkflowName;

final class InMemoryWorkflowStateStore implements WorkflowStateStore
{
    /** @var array<string, WorkflowStateRecord> */
    private array $records = [];

    public function load(WorkflowName $workflow, string $subjectId): ?WorkflowStateRecord
    {
        return $this->records[self::key($workflow, $subjectId)] ?? null;
    }

    public function initialize(WorkflowName $workflow, string $subjectId, WorkflowState $initial): WorkflowStateRecord
    {
        $key = self::key($workflow, $subjectId);

        return $this->records[$key] ??= new WorkflowStateRecord($initial, 0);
    }

    public function compareAndSet(
        WorkflowName $workflow,
        string $subjectId,
        int $expectedVersion,
        WorkflowState $next,
    ): WorkflowStateRecord {
        $key = self::key($workflow, $subjectId);
        $current = $this->records[$key] ?? null;

        if ($current === null || $current->version !== $expectedVersion) {
            throw new ConcurrentTransition();
        }

        if ($expectedVersion === PHP_INT_MAX) {
            throw new \OverflowException('Workflow state version overflow.');
        }

        return $this->records[$key] = new WorkflowStateRecord($next, $expectedVersion + 1);
    }

    private static function key(WorkflowName $workflow, string $subjectId): string
    {
        return $workflow->value . "\0" . $subjectId;
    }
}
