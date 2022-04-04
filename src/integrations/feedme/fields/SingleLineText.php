<?php
namespace verbb\formie\integrations\feedme\fields;

use verbb\formie\fields\formfields\SingleLineText as SingleLineTextField;

class SingleLineText extends DefaultField
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static string $class = SingleLineTextField::class;
    public static string $name = 'SingleLineText';

}
