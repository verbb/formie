<?php
namespace verbb\formie\base;

use craft\base\ComponentInterface;
use verbb\formie\models\FieldLayout;

interface NestedFieldInterface extends ComponentInterface
{
    // Public Methods
    // =========================================================================

    public function getRows(): array;
    public function setRows(array $rows, bool $duplicate = false);
    public function getFieldLayout(): ?FieldLayout;
    public function setFieldLayout(FieldLayout $fieldLayout): void;
    public function getFormFieldContext(): string;
}
