<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\fields\CommerceVariants as FeedMeVariants;

class Variants extends FeedMeVariants
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;

    
    // Properties
    // =========================================================================

    public static $name = 'Variants';
    public static $class = 'verbb\formie\fields\formfields\Variants';

}
