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

}
