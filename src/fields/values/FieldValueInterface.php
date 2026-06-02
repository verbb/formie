<?php
namespace verbb\formie\fields\values;

interface FieldValueInterface
{
    // Static Methods
    // =========================================================================

    public static function capabilityTypes(): array;
    public static function toClientValueFrom(mixed $value): mixed;


    // Public Methods
    // =========================================================================

    public function isEmpty(): bool;
    public function toValueArray(): array;
    public function toClientValue(): mixed;
    public function toValueString(): string;
    public function canResolvePath(string $path): bool;
    public function getPathValue(string $path): mixed;
}
