<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\fields\Number as FeedMeNumber;

class Number extends FeedMeNumber
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static $name = 'Number';
    public static $class = 'verbb\formie\fields\formfields\Number';

}
