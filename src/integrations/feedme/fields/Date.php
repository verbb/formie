<?php
namespace verbb\formie\integrations\feedme\fields;

use verbb\formie\fields\Date as DateField;

use craft\feedme\fields\Date as FeedMeDate;

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
