<?php

declare(strict_types=1);

namespace Contempt\Workflow\Tests;

use Contempt\Workflow\Definition\TransitionDefinition;
use Contempt\Workflow\Definition\TransitionName;
use Contempt\Workflow\Definition\WorkflowDefinition;
use Contempt\Workflow\Definition\WorkflowName;
use Contempt\Workflow\Engine\TransitionDenied;
use Contempt\Workflow\Engine\UnknownTransition;
use Contempt\Workflow\Engine\WorkflowEngine;
use Contempt\Workflow\Guard\TransitionGuard;
use Contempt\Workflow\State\InMemoryWorkflowStateStore;
use Contempt\Workflow\State\WorkflowState;
use Contempt\Workflow\Subject\WorkflowSubject;
use Contempt\Workflow\Telemetry\WorkflowObserver;
use Contempt\Workflow\Telemetry\WorkflowTransitioned;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WorkflowDefinition::class)]
#[CoversClass(WorkflowEngine::class)]
#[CoversClass(InMemoryWorkflowStateStore::class)]
final class WorkflowEngineTest extends TestCase
{
    public function testTransitionPersistsTypedStateVersionAndEmitsControlledEvent(): void
    {
        $observer = new RecordingWorkflowObserver();
        $engine = new WorkflowEngine(new InMemoryWorkflowStateStore(), $observer);
        $definition = self::definition();
        $order = new Order('order-1', 100);

        $result = $engine->transition($definition, $order, new TransitionName('pay'));

        self::assertSame(OrderState::New, $result->from);
        self::assertSame(OrderState::Paid, $result->current);
        self::assertSame(1, $result->version);
        self::assertCount(1, $observer->events);
        self::assertSame('order-1', $observer->events[0]->subjectId);
        self::assertSame('new', $observer->events[0]->from);
        self::assertSame('paid', $observer->events[0]->to);

        $second = $engine->transition($definition, $order, new TransitionName('ship'));
        self::assertSame(OrderState::Paid, $second->from);
        self::assertSame(OrderState::Shipped, $second->current);
        self::assertSame(2, $second->version);
    }

    public function testDeniedAndUnknownTransitionsDoNotMutateState(): void
    {
        $store = new InMemoryWorkflowStateStore();
        $engine = new WorkflowEngine($store);
        $definition = self::definition(new MinimumTotalGuard(500));
        $order = new Order('order-2', 100);

        try {
            $engine->transition($definition, $order, new TransitionName('pay'));
            self::fail('Guard must deny transition.');
        } catch (TransitionDenied) {
        }

        self::assertSame(OrderState::New, $engine->state($definition, $order)->state);

        try {
            $engine->transition($definition, $order, new TransitionName('ship'));
            self::fail('A transition from the wrong current state must fail.');
        } catch (UnknownTransition) {
        }

        self::assertSame(0, $engine->state($definition, $order)->version);
    }

    public function testDefinitionRejectsAmbiguousEdgesAndForeignStateEnums(): void
    {
        $pay = new TransitionDefinition(new TransitionName('pay'), [OrderState::New], OrderState::Paid);

        try {
            new WorkflowDefinition(new WorkflowName('order'), OrderState::class, OrderState::New, [$pay, $pay]);
            self::fail('Duplicate edges must fail compilation.');
        } catch (\InvalidArgumentException) {
        }

        $this->expectException(\InvalidArgumentException::class);
        new WorkflowDefinition(
            new WorkflowName('order'),
            OrderState::class,
            OrderState::New,
            [new TransitionDefinition(new TransitionName('bad'), [OtherState::Foreign], OrderState::Paid)],
        );
    }

    /** @return WorkflowDefinition<OrderState> */
    private static function definition(?TransitionGuard $guard = null): WorkflowDefinition
    {
        return new WorkflowDefinition(
            new WorkflowName('order'),
            OrderState::class,
            OrderState::New,
            [
                new TransitionDefinition(new TransitionName('pay'), [OrderState::New], OrderState::Paid, $guard === null ? [] : [$guard]),
                new TransitionDefinition(new TransitionName('ship'), [OrderState::Paid], OrderState::Shipped),
            ],
        );
    }
}

enum OrderState: string implements WorkflowState
{
    case New = 'new';
    case Paid = 'paid';
    case Shipped = 'shipped';
}

enum OtherState: string implements WorkflowState
{
    case Foreign = 'foreign';
}

final readonly class Order implements WorkflowSubject
{
    public function __construct(private string $id, public int $total) {}

    public function workflowSubjectId(): string
    {
        return $this->id;
    }
}

final readonly class MinimumTotalGuard implements TransitionGuard
{
    public function __construct(private int $minimum) {}

    public function allows(WorkflowSubject $subject): bool
    {
        return $subject instanceof Order && $subject->total >= $this->minimum;
    }
}

final class RecordingWorkflowObserver implements WorkflowObserver
{
    /** @var list<WorkflowTransitioned> */
    public array $events = [];

    public function transitioned(WorkflowTransitioned $event): void
    {
        $this->events[] = $event;
    }
}
