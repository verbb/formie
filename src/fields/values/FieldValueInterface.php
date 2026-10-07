<?php
namespace verbb\formie\fields\values;

use verbb\formie\base\FieldValueInterface as BaseFieldValueInterface;

use Stringable;

interface FieldValueInterface extends Stringable, BaseFieldValueInterface
{
    // Public Methods
    // =========================================================================

    public function isEmpty(): bool;
    public function canResolvePath(string $path): bool;
    public function getPathValue(string $path): mixed;
}
