<?php
namespace verbb\formie\integrations\feedme\fields;

use verbb\formie\fields\Password as PasswordField;

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
