<?php
namespace verbb\formie\base;

/**
 * Opts a parent field into separate report columns for its direct children.
 * Returned fields must belong to its normal child layout, in report order.
 * Repeating rows are not implicitly flattened into columns.
 */
interface ReportSubfieldsInterface extends ParentFieldInterface
{
    // Public Methods
    // =========================================================================

    public function getReportSubfields(): array;
}
