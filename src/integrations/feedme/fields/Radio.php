<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\fields\RadioButtons as FeedMeRadioButtons;

class Radio extends FeedMeRadioButtons
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;

    
    // Properties
    // =========================================================================

    public static $name = 'Radio';
    public static $class = 'verbb\formie\fields\formfields\Radio';

}
