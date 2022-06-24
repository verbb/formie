<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\fields\Entries as FeedMeEntries;

class Entries extends FeedMeEntries
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static $name = 'Entries';
    public static $class = 'verbb\formie\fields\formfields\Entries';

}
