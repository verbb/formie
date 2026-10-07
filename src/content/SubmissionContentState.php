<?php
namespace verbb\formie\content;

use verbb\formie\conditions\ConditionState;
use verbb\formie\models\SubmissionUploadClaims;

class SubmissionContentState
{
    // Properties
    // =========================================================================

    public array $rawValuesByUid = [];
    public array $normalizedValuesByUid = [];
    public array $orphanedValuesByUid = [];
    public ?SubmissionFieldCollection $fieldCollection = null;
    public array $currentPageFieldHandleMapsByPageId = [];
    public array $uploadedDataFiles = [];
    public bool $isMergingPartialPayload = false;
    public ?SubmissionUploadClaims $uploadClaims = null;
    public ConditionState $conditions;


    // Public Methods
    // =========================================================================

    public function __construct()
    {
        $this->conditions = new ConditionState();
    }

    public function resetFieldCollection(): void
    {
        $this->conditions->invalidate();
        $this->fieldCollection = null;
        $this->currentPageFieldHandleMapsByPageId = [];
    }
}
