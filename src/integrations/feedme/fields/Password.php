<?php
namespace verbb\formie\integrations\feedme\fields;

use verbb\formie\fields\formfields\Password as PasswordField;

class Password extends DefaultField
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;

    
    // Properties
    // =========================================================================

    public static $name = 'Password';
    public static $class = PasswordField::class;

}
