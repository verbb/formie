<?php
namespace verbb\formie\integrations\feedme\fields;

use verbb\formie\fields\Tags as TagsField;

use craft\feedme\fields\Tags as FeedMeTags;

class Tags extends FeedMeTags
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static string $class = TagsField::class;
    public static string $name = 'Tags';


    // Public Methods
    // =========================================================================

    // Templates
    // =========================================================================

    public function getMappingTemplate(): string
    {
        return 'formie/integrations/feedme/fields/tags';
    }
}
