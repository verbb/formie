<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\fields\Date as FeedMeDate;
use verbb\formie\fields\formfields\Date as DateField;

class Date extends FeedMeDate
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static string $class = DateField::class;
    public static string $name = 'Date';

}
