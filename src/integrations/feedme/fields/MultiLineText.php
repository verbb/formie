<?php
namespace verbb\formie\integrations\feedme\fields;

use verbb\formie\fields\formfields\MultiLineText as MultiLineTextField;

class MultiLineText extends DefaultField
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;

    
    // Properties
    // =========================================================================

    public static $name = 'MultiLineText';
    public static $class = MultiLineTextField::class;

}
