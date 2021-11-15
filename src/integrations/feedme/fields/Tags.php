<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\fields\Tags as FeedMeTags;

class Tags extends FeedMeTags
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;

    
    // Properties
    // =========================================================================

    public static $name = 'Tags';
    public static $class = 'verbb\formie\fields\formfields\Tags';

}
