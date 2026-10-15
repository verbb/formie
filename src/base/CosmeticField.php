<?php
namespace verbb\formie\base;

use verbb\formie\fields\definitions\FieldValueType;

use craft\base\ElementInterface;

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
        return $this->callLegacyFieldMethod('hasEmailLabel', 'hasReferenceBlockLabel', [], fn() => false);
    }

    public function hasReferenceBlockPlaceholder(): bool
    {
        return $this->callLegacyFieldMethod('hasEmailPlaceholder', 'hasReferenceBlockPlaceholder', [], fn() => false);
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
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
