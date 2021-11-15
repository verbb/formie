<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\fields\Checkboxes as FeedMeCheckboxes;

class Checkboxes extends FeedMeCheckboxes
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;

    
    // Properties
    // =========================================================================

    public static $name = 'Checkboxes';
    public static $class = 'verbb\formie\fields\formfields\Checkboxes';

}
