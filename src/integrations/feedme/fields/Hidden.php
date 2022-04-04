<?php
namespace verbb\formie\integrations\feedme\fields;

use verbb\formie\fields\formfields\Hidden as HiddenField;

class Hidden extends DefaultField
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static string $class = HiddenField::class;
    public static string $name = 'Hidden';

}
