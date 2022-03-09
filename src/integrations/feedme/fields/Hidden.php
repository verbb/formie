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

    public static $name = 'Hidden';
    public static $class = HiddenField::class;

}
