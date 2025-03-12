<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\fields\Tags as FeedMeTags;
use verbb\formie\fields\Tags as TagsField;

class Tags extends FeedMeTags
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static string $class = TagsField::class;
    public static string $name = 'Tags';


    // Templates
    // =========================================================================

    public function getMappingTemplate(): string
    {
        return 'formie/integrations/feedme/fields/tags';
    }

}
