<?php
namespace verbb\formie\events;

use verbb\formie\models\SubmissionRequest;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskResult;

use craft\events\CancelableEvent;

class SubmissionWorkflowTaskEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public ?WorkflowContext $context = null;
    public ?SubmissionRequest $request = null;
    public string $stage = '';
    public string $task = '';
    public ?TaskResult $result = null;
}
