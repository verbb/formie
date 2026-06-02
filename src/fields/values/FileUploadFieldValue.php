<?php
namespace verbb\formie\fields\values;

class FileUploadFieldValue extends ElementFieldValue
{
    // Static Methods
    // =========================================================================

    public static function capabilityTypes(): array
    {
        return ['element', 'file'];
    }


    // Properties
    // =========================================================================

    public array $assetIds = [];
    

    // Public Methods
    // =========================================================================

    public function isEmpty(): bool
    {
        return $this->assetIds === [] && $this->elementIds === [];
    }

    public function toClientValue(): mixed
    {
        return $this->assetIds !== [] ? $this->assetIds : $this->elementIds;
    }
}
