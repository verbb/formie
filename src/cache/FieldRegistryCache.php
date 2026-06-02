<?php
namespace verbb\formie\cache;

class FieldRegistryCache
{
    // Properties
    // =========================================================================

    public array $registeredFields = [];
    public array $registeredFieldTypes = [];
    public array $resolvedRegisteredFieldTypes = [];
    public array $registeredFieldInstancesByType = [];


    // Public Methods
    // =========================================================================

    public function reset(): void
    {
        $this->registeredFields = [];
        $this->registeredFieldTypes = [];
        $this->resolvedRegisteredFieldTypes = [];
        $this->registeredFieldInstancesByType = [];
    }
}
