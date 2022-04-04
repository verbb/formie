<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\fields\CommerceVariants as FeedMeVariants;
use verbb\formie\fields\formfields\Variants as VariantsField;

class Variants extends FeedMeVariants
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static string $class = VariantsField::class;
    public static string $name = 'Variants';

}
