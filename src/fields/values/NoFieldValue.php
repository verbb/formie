<?php
namespace verbb\formie\fields\values;

class NoFieldValue extends BaseFieldValue
{
    // Static Methods
    // =========================================================================

    public static function toClientValueFrom(mixed $value): mixed
    {
        return $value;
    }


    // Public Methods
    // =========================================================================

    public function isEmpty(): bool
    {
        return true;
    }

    public function toValueArray(): array
    {
        return [];
    }

    public function toClientValue(): mixed
    {
        return null;
    }

    public function toValueString(): string
    {
        return '';
    }

    public function canResolvePath(string $path): bool
    {
        return false;
    }

    public function getPathValue(string $path): mixed
    {
        return null;
    }
}
