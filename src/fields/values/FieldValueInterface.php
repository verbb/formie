<?php
namespace verbb\formie\fields\values;

interface FieldValueInterface extends \Stringable, \verbb\formie\base\FieldValueInterface
{
    // Public Methods
    // =========================================================================

    public function isEmpty(): bool;
    public function canResolvePath(string $path): bool;
    public function getPathValue(string $path): mixed;
}
