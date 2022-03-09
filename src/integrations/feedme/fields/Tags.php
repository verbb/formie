<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\fields\Tags as FeedMeTags;
use verbb\formie\fields\formfields\Tags as TagsField;

class Tags extends FeedMeTags
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;

    
    // Properties
    // =========================================================================

    public static $name = 'Tags';
    public static $class = TagsField::class;

}
