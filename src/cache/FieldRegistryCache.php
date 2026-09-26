<?php
namespace verbb\formie\cache;

class FieldRegistryCache
{
    // Properties
    // =========================================================================

    public array $registeredFieldTypes = [];
    public array $resolvedRegisteredFieldTypes = [];


    // Public Methods
    // =========================================================================

    public function reset(): void
    {
        $this->registeredFieldTypes = [];
        $this->resolvedRegisteredFieldTypes = [];
    }
}
