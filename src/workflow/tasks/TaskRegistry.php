<?php
namespace verbb\formie\workflow\tasks;

use verbb\formie\enums\workflow\Task;
use verbb\formie\workflow\TaskDefinition;

use InvalidArgumentException;

final class TaskRegistry
{
    // Properties
    // =========================================================================

    private array $_afterTails = [];


    // Public Methods
    // =========================================================================

    public function __construct(private array $_tasks)
    {
    }

    public function all(): array
    {
        return $this->_tasks;
    }

    public function insertBefore(Task|string $anchor, TaskDefinition $task): void
    {
        $this->_insert($anchor, $task, false);
    }

    public function insertAfter(Task|string $anchor, TaskDefinition $task): void
    {
        $this->_insert($anchor, $task, true);
    }

    public function prepend(TaskDefinition $task): void
    {
        $this->_assertUnique($task);
        array_unshift($this->_tasks, $task);
    }

    public function append(TaskDefinition $task): void
    {
        $this->_assertUnique($task);
        $this->_tasks[] = $task;
    }


    // Private Methods
    // =========================================================================

    private function _insert(Task|string $anchor, TaskDefinition $task, bool $after): void
    {
        $anchor = $anchor instanceof Task ? $anchor->value : $anchor;
        $this->_assertUnique($task);
        $index = null;

        foreach ($this->_tasks as $i => $candidate) {
            if ($candidate->id === $anchor && $candidate->publicAnchor) {
                $index = $i;
                break;
            }
        }

        if ($index === null) {
            throw new InvalidArgumentException('Unknown or internal task anchor: ' . $anchor);
        }

        if ($after && isset($this->_afterTails[$anchor])) {
            foreach ($this->_tasks as $i => $candidate) {
                if ($candidate->id === $this->_afterTails[$anchor]) {
                    $index = $i;
                    break;
                }
            }
        }

        array_splice($this->_tasks, $index + ($after ? 1 : 0), 0, [$task]);
        if ($after) {
            $this->_afterTails[$anchor] = $task->id;
        }
    }

    private function _assertUnique(TaskDefinition $task): void
    {
        if (Task::tryFrom($task->id) || str_starts_with($task->id, 'persist.') || str_starts_with($task->id, 'finalize.')) {
            throw new InvalidArgumentException('Custom task IDs must use an extension namespace.');
        }
        foreach ($this->_tasks as $existing) {
            if ($existing->id === $task->id) {
                throw new InvalidArgumentException('Duplicate task ID: ' . $task->id);
            }
        }
    }
}
