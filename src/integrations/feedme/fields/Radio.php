<?php
namespace verbb\formie\integrations\feedme\fields;

use verbb\formie\fields\Radio as RadioField;

use craft\feedme\fields\RadioButtons as FeedMeRadioButtons;

class Radio extends FeedMeRadioButtons
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static string $class = RadioField::class;
    public static string $name = 'Radio';
}
