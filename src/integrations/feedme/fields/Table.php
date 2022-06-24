<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\fields\Table as FeedMeTable;

class Table extends FeedMeTable
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static $name = 'Table';
    public static $class = 'verbb\formie\fields\formfields\Table';

}
