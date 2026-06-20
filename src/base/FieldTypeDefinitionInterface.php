<?php
namespace verbb\formie\base;

interface FieldTypeDefinitionInterface
{
    // Static Methods
    // =========================================================================

    public static function defineFieldType(): array;
    public static function getFieldTypeDefinition(): array;
    public static function translatableProperties(): array;
}
