<?php
namespace verbb\formie\events;

use verbb\formie\models\SubmissionCommand;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

use yii\base\Event;

class SubmissionWorkflowStageEvent extends Event
{
    // Properties
    // =========================================================================

    public ?WorkflowContext $context = null;
    public ?SubmissionCommand $command = null;
    public string $stage = '';
    public ?TaskResult $result = null;
}
