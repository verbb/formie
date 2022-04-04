<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\fields\Dropdown as FeedMeDropdown;
use verbb\formie\fields\formfields\Dropdown as DropdownField;

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
