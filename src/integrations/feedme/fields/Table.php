<?php
namespace verbb\formie\integrations\feedme\fields;

use verbb\formie\fields\Table as TableField;

use craft\feedme\fields\Table as FeedMeTable;

class Table extends FeedMeTable
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static string $class = TableField::class;
    public static string $name = 'Table';
}
