<?php
namespace verbb\formie\integrations\feedme\fields;

use verbb\formie\fields\formfields\Email as EmailField;

class Email extends DefaultField
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;

    
    // Properties
    // =========================================================================

    public static $name = 'Email';
    public static $class = EmailField::class;

}
