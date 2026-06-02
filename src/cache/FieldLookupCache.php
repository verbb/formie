<?php
namespace verbb\formie\cache;

class FieldLookupCache
{
    // Properties
    // =========================================================================

    public array $layouts = [];
    public array $layoutsById = [];
    public array $pagesById = [];
    public array $rowsById = [];
    public ?array $fields = null;
    public array $fieldsForForm = [];
    public array $fieldConfigById = [];
    public array $fieldConfigByReference = [];
    public array $decodedFieldSettings = [];
    public array $existingFieldsByExcludeFormId = [];


    // Public Methods
    // =========================================================================

    public function reset(): void
    {
        $this->layouts = [];
        $this->layoutsById = [];
        $this->pagesById = [];
        $this->rowsById = [];
        $this->fields = null;
        $this->fieldsForForm = [];
        $this->fieldConfigById = [];
        $this->fieldConfigByReference = [];
        $this->decodedFieldSettings = [];
        $this->existingFieldsByExcludeFormId = [];
    }
}
