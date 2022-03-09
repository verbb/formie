<?php
namespace verbb\formie\base;

use craft\base\ComponentInterface;
use verbb\formie\models\FieldLayout;

interface NestedFieldInterface extends ComponentInterface
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the rows from the field layout.
     *
     * @return array
     */
    public function getRows(): array;

    /**
     * Sets the field's field layout from an array of rows.
     *
     * @param array $rows
     * @param bool $duplicate
     */
    public function setRows(array $rows, bool $duplicate = false);

    /**
     * Returns the field's field layout.
     *
     * @return FieldLayout|null
     */
    public function getFieldLayout(): ?FieldLayout;

    /**
     * Sets the field's field layout.
     *
     * @param FieldLayout $fieldLayout
     */
    public function setFieldLayout(FieldLayout $fieldLayout): void;

    /**
     * Returns the field context.
     *
     * @return string
     */
    public function getFormFieldContext(): string;
}
