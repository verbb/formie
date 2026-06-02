<?php
namespace verbb\formie\base;

abstract class CosmeticField extends Field implements CosmeticFieldInterface
{
    // Public Methods
    // =========================================================================

    public function getIsCosmetic(): bool
    {
        return true;
    }

    public function hasLabel(): bool
    {
        return false;
    }

    public function hasReferenceBlockLabel(): bool
    {
        return false;
    }

    public function hasReferenceBlockPlaceholder(): bool
    {
        return false;
    }

}
