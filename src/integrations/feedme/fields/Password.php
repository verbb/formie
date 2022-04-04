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

    public static string $class = PasswordField::class;
    public static string $name = 'Password';

}
