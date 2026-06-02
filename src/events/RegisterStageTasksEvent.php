<?php
namespace verbb\formie\events;

use verbb\formie\Formie;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskRegistry;

use yii\base\Event;

class RegisterStageTasksEvent extends Event
{
    // Properties
    // =========================================================================

    public string $stage = '';
    public array $tasks = [];

    // Public Methods
    // =========================================================================
    
    public function hasTask(string $taskName): bool
    {
        $registry = new TaskRegistry($this->tasks);

        return $registry->has($taskName);
    }

    public function insertTaskBefore(string $anchorTaskName, TaskInterface $task): bool
    {
        $registry = new TaskRegistry($this->tasks);

        if (!$registry->insertBefore($anchorTaskName, $task)) {
            Formie::warning('Unable to insert workflow task "{task}" before "{anchor}" because anchor task was not found.', [
                'task' => $task->getName(),
                'anchor' => $anchorTaskName,
            ]);

            return false;
        }

        $this->tasks = $registry->all();

        return true;
    }

    public function insertTaskAfter(string $anchorTaskName, TaskInterface $task): bool
    {
        $registry = new TaskRegistry($this->tasks);

        if (!$registry->insertAfter($anchorTaskName, $task)) {
            Formie::warning('Unable to insert workflow task "{task}" after "{anchor}" because anchor task was not found.', [
                'task' => $task->getName(),
                'anchor' => $anchorTaskName,
            ]);

            return false;
        }

        $this->tasks = $registry->all();

        return true;
    }
}
