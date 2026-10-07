<?php
namespace verbb\formie\integrations\feedme\fields;

use verbb\formie\fields\Agree as AgreeField;

use craft\feedme\fields\Lightswitch as FeedMeLightswitch;

class Agree extends FeedMeLightswitch
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static string $class = AgreeField::class;
    public static string $name = 'Agree';
}
