<?php
namespace verbb\formie\events;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\workflow\WorkflowContext;

use yii\base\Event;

class SubmissionCompleteEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Submission $submission = null;
    public ?Form $form = null;

    /**
     * Present when completion happened through the submission workflow.
     * Null for control-panel / direct element saves that mark a submission complete.
     */
    public ?SubmissionRequest $request = null;

    /**
     * Present when completion happened through the submission workflow.
     */
    public ?WorkflowContext $context = null;

    /**
     * The page that was submitted when the form became complete, if known.
     */
    public ?FieldLayoutPage $fromPage = null;
}
