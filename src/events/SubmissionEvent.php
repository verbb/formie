<?php
namespace verbb\formie\events;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

use craft\events\CancelableEvent;

use yii\web\Response;

class SubmissionEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public ?Submission $submission = null;
    public ?Form $form = null;
    public ?string $handle = null;
    public ?string $submitAction = null;
    public ?bool $success = null;
    public ?string $redirectUrl = null;
    public ?Response $response = null;
    
}
