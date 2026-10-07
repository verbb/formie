<?php
namespace verbb\formie\integrations\feedme\fields;

use verbb\formie\fields\Dropdown as DropdownField;

use craft\feedme\fields\Dropdown as FeedMeDropdown;

class Dropdown extends FeedMeDropdown
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static string $class = DropdownField::class;
    public static string $name = 'Dropdown';
}
