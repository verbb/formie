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

    public static string $class = CheckboxesField::class;
    public static string $name = 'Checkboxes';

}
