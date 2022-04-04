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

    public static string $class = EmailField::class;
    public static string $name = 'Email';

}
