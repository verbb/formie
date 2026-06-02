<?php
namespace verbb\formie\models;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\FieldLayoutPage;

use craft\base\Model;

class SubmissionResponse extends Model
{
    // Properties
    // =========================================================================

    public bool $success = false;
    public ?Submission $submission = null;
    public ?Form $form = null;
    public ?FieldLayoutPage $nextPage = null;
    public ?array $workflowResult = null;
    public ?string $paymentStatus = null;
    public ?string $paymentMessage = null;
    public ?string $paymentRedirectUrl = null;
    public ?array $paymentAction = null;
    public ?array $paymentDecision = null;
}
