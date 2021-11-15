<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\fields\Categories as FeedMeCategories;

class Categories extends FeedMeCategories
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;

    
    // Properties
    // =========================================================================

    public static $name = 'Categories';
    public static $class = 'verbb\formie\fields\formfields\Categories';

}
