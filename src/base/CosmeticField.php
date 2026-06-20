<?php
namespace verbb\formie\base;

abstract class CosmeticField extends Field implements CosmeticFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function translatableProperties(): array
    {
        return [];
    }

    public static function translatableRichTextProperties(): array
    {
        return [];
    }


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
