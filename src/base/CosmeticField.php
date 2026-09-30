<?php
namespace verbb\formie\base;

use verbb\formie\fields\definitions\FieldValueType;

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

    public function normalizeValue(mixed $value, ?\craft\base\ElementInterface $element): mixed
    {
        return null;
    }


    // Protected Methods
    // =========================================================================

    protected function defineValueType(): FieldValueType
    {
        return FieldValueType::none();
    }
}
