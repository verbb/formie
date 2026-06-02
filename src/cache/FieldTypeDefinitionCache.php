<?php
namespace verbb\formie\cache;

class FieldTypeDefinitionCache
{
    // Properties
    // =========================================================================

    public array $definitionsByClass = [];
    public array $groupedDefinitionsBySet = [];


    // Public Methods
    // =========================================================================

    public function reset(): void
    {
        $this->definitionsByClass = [];
        $this->groupedDefinitionsBySet = [];
    }
}
