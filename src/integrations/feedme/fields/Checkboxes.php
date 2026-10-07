<?php
namespace verbb\formie\integrations\feedme\fields;

use verbb\formie\fields\Checkboxes as CheckboxesField;

use craft\feedme\fields\Checkboxes as FeedMeCheckboxes;

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
