<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\fields\RadioButtons as FeedMeRadioButtons;
use verbb\formie\fields\formfields\Radio as RadioField;

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
