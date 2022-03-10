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

    public static $class = PasswordField::class;
    public static $name = 'Password';

}
