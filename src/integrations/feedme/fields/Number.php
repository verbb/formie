<?php
namespace verbb\formie\integrations\feedme\fields;

use verbb\formie\fields\Number as NumberField;

use craft\feedme\fields\Number as FeedMeNumber;

class Number extends FeedMeNumber
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static string $class = NumberField::class;
    public static string $name = 'Number';
}
