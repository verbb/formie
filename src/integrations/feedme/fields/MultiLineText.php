<?php
namespace verbb\formie\integrations\feedme\fields;

use verbb\formie\fields\MultiLineText as MultiLineTextField;

class MultiLineText extends DefaultField
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static string $class = MultiLineTextField::class;
    public static string $name = 'MultiLineText';

}
