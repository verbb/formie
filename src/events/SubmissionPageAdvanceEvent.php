<?php
namespace verbb\formie\events;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\workflow\WorkflowContext;

use yii\base\Event;

class SubmissionPageAdvanceEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Submission $submission = null;
    public ?Form $form = null;
    public ?SubmissionRequest $request = null;
    public ?WorkflowContext $context = null;

    /**
     * The page that was just accepted (page 1 when moving to page 2).
     */
    public ?FieldLayoutPage $fromPage = null;

    /**
     * The next reachable page Formie will show.
     */
    public ?FieldLayoutPage $toPage = null;
}
