<?php
namespace verbb\formie\compatibility\fields;

use verbb\formie\helpers\StringHelper;

trait ParentFieldCompatibility
{
    // Properties
    // =========================================================================

    public mixed $contentTable = null;


    // Static Methods
    // =========================================================================

    public static function lowerDisplayName(): string
    {
        return StringHelper::toLowerCase(static::displayName());
    }

    public static function defaultCardAttributes(): array
    {
        return [];
    }


    // Protected Methods
    // =========================================================================

    protected function addCompatibilitySettingsAttributes(array $attributes): array
    {
        $attributes[] = 'contentTable';

        return $attributes;
    }
}
