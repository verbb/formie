<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\fields\Entries as FeedMeEntries;
use verbb\formie\fields\formfields\Entries as EntriesField;

class Entries extends FeedMeEntries
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;

    
    // Properties
    // =========================================================================

    public static $name = 'Entries';
    public static $class = EntriesField::class;

}
