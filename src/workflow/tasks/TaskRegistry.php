<?php
namespace verbb\formie\workflow\tasks;

class TaskRegistry
{
    // Properties
    // =========================================================================

    private array $tasks = [];


    // Public Methods
    // =========================================================================

    public function __construct(array $tasks = [])
    {
        foreach ($tasks as $task) {
            $this->register($task);
        }
    }

    public function register(TaskInterface $task): void
    {
        $this->tasks[] = $task;
    }

    public function has(string $taskName): bool
    {
        foreach ($this->tasks as $task) {
            if ($task->getName() === $taskName) {
                return true;
            }
        }

        return false;
    }

    public function insertBefore(string $anchorTaskName, TaskInterface $task): bool
    {
        foreach ($this->tasks as $index => $existingTask) {
            if ($existingTask->getName() === $anchorTaskName) {
                array_splice($this->tasks, $index, 0, [$task]);

                return true;
            }
        }

        return false;
    }

    public function insertAfter(string $anchorTaskName, TaskInterface $task): bool
    {
        foreach ($this->tasks as $index => $existingTask) {
            if ($existingTask->getName() === $anchorTaskName) {
                array_splice($this->tasks, $index + 1, 0, [$task]);

                return true;
            }
        }

        return false;
    }

    public function all(): array
    {
        return $this->tasks;
    }
}
