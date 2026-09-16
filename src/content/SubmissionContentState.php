<?php
namespace verbb\formie\content;

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


    // Public Methods
    // =========================================================================

    public function resetFieldCollection(): void
    {
        $this->fieldCollection = null;
        $this->currentPageFieldHandleMapsByPageId = [];
    }
}
