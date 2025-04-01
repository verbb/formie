<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\fields\Entries as FeedMeEntries;
use verbb\formie\fields\Entries as EntriesField;

class Entries extends FeedMeEntries
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static string $class = EntriesField::class;
    public static string $name = 'Entries';


    // Templates
    // =========================================================================

    public function getMappingTemplate(): string
    {
        return 'formie/integrations/feedme/fields/entries';
    }

}
