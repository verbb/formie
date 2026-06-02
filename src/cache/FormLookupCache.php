<?php
namespace verbb\formie\cache;

class FormLookupCache
{
    // Properties
    // =========================================================================

    public ?array $allForms = null;
    public ?array $allFormsWithLayouts = null;
    public array $formsByLayoutId = [];
    public array $formsById = [];
    public array $formsByHandle = [];
    public array $formsByUid = [];
    public array $elementsByIdAndSite = [];
    public array $fieldsById = [];


    // Public Methods
    // =========================================================================

    public function reset(): void
    {
        $this->allForms = null;
        $this->allFormsWithLayouts = null;
        $this->formsByLayoutId = [];
        $this->formsById = [];
        $this->formsByHandle = [];
        $this->formsByUid = [];
        $this->elementsByIdAndSite = [];
        $this->fieldsById = [];
    }
}
