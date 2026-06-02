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


    // Public Methods
    // =========================================================================

    public function resetFieldCollection(): void
    {
        $this->fieldCollection = null;
        $this->currentPageFieldHandleMapsByPageId = [];
    }
}
