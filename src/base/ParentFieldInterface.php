<?php
namespace verbb\formie\base;

use verbb\formie\models\FieldLayout;

use craft\base\ComponentInterface;

interface ParentFieldInterface extends ComponentInterface
{
    // Public Methods
    // =========================================================================

    public function getRows(): array;
    public function getFields(): array;
    public function getFieldByHandle(string $handle): ?FieldInterface;
    public function getFieldLayout(): FieldLayout;
    public function hasFieldLayout(): bool;
}
