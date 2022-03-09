<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\fields\Checkboxes as FeedMeCheckboxes;
use verbb\formie\fields\formfields\Checkboxes as CheckboxesField;

class Checkboxes extends FeedMeCheckboxes
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;

    
    // Properties
    // =========================================================================

    public static $name = 'Checkboxes';
    public static $class = CheckboxesField::class;

}
