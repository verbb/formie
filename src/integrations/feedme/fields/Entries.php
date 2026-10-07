<?php
namespace verbb\formie\integrations\feedme\fields;

use verbb\formie\fields\Entries as EntriesField;

use craft\feedme\fields\Entries as FeedMeEntries;

class Entries extends FeedMeEntries
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static string $class = EntriesField::class;
    public static string $name = 'Entries';


    // Public Methods
    // =========================================================================

    // Templates
    // =========================================================================

    public function getMappingTemplate(): string
    {
        return 'formie/integrations/feedme/fields/entries';
    }
}
