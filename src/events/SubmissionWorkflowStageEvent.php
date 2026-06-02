<?php
namespace verbb\formie\events;

use verbb\formie\models\SubmissionRequest;
use verbb\formie\workflow\StageResult;
use verbb\formie\workflow\WorkflowContext;

use craft\events\CancelableEvent;

class SubmissionWorkflowStageEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public ?WorkflowContext $context = null;
    public ?SubmissionRequest $request = null;
    public string $stage = '';
    public ?StageResult $result = null;
}
