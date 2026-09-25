<?php
namespace verbb\formie\events;

use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\workflow\TaskDefinition;
use verbb\formie\workflow\tasks\TaskRegistry;

use yii\base\Event;

class RegisterStageTasksEvent extends Event
{
    // Properties
    // =========================================================================

    public Stage $stage;
    public TaskRegistry $registry;


    // Public Methods
    // =========================================================================

    public function insertTaskBefore(Task|string $anchor, TaskDefinition $task): void
    {
        $this->registry->insertBefore($anchor, $task);
    }

    public function insertTaskAfter(Task|string $anchor, TaskDefinition $task): void
    {
        $this->registry->insertAfter($anchor, $task);
    }

    public function prepend(TaskDefinition $task): void
    {
        $this->registry->prepend($task);
    }

    public function append(TaskDefinition $task): void
    {
        $this->registry->append($task);
    }
}
