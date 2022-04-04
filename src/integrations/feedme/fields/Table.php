<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\fields\Table as FeedMeTable;
use verbb\formie\fields\formfields\Table as TableField;

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
