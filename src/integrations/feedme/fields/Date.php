<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\fields\Date as FeedMeDate;

class Date extends FeedMeDate
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;

    
    // Properties
    // =========================================================================

    public static $name = 'Date';
    public static $class = 'verbb\formie\fields\formfields\Date';

}
