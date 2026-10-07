<?php
namespace verbb\formie\models;

use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionOperation;

use craft\base\Model;

class IntegrationTriggerRequest extends Model
{
    // Properties
    // =========================================================================

    public string $source;
    public Submission $submission;
    public SubmissionOperation $operation = SubmissionOperation::SUBMIT;
    public ?string $triggerEvent = null;
    public bool $operatorInitiated = false;
    public ?Integration $integration = null;
}
