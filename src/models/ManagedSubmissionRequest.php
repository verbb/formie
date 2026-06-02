<?php
namespace verbb\formie\models;

use verbb\formie\services\SubmissionWorkflow;

use craft\base\Model;

class ManagedSubmissionRequest extends Model
{
    // Properties
    // =========================================================================

    public string $handle = '';
    public string $processMode = SubmissionWorkflow::PROCESS_MODE_SUBMIT;
    public ?int $siteId = null;
    public ?string $renderId = null;
    public ?string $requestToken = null;
    public ?string $draftContext = null;
    public ?string $draftContextToken = null;
    public ?string $resumeToken = null;
    public ?int $submissionId = null;
    public ?string $submissionUid = null;
    public ?string $submissionEditToken = null;
    public ?string $submitAction = null;
    public ?int $pageId = null;
    public ?int $targetPageId = null;
    public string $fieldParamNamespace = 'fields';
    public ?int $userId = null;
}
