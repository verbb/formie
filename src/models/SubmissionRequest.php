<?php
namespace verbb\formie\models;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\services\SubmissionWorkflow;

use craft\base\Model;

class SubmissionRequest extends Model
{
    // Properties
    // =========================================================================

    public string $processMode;
    public Form $form;
    public Submission $submission;
    public string $submitAction = SubmissionWorkflow::SUBMIT_ACTION_SUBMIT;
    public ?int $pageId = null;
    public ?int $targetPageId = null;
    public ?int $siteId = null;
    public ?string $requestToken = null;
    public ?string $draftContext = null;
    public bool $clearConditionallyHiddenFields = false;
}
