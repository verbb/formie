<?php
namespace verbb\formie\integrations\feedme\fields;

use verbb\formie\fields\Phone as PhoneField;

class Phone extends DefaultField
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static string $class = PhoneField::class;
    public static string $name = 'Phone';

}
