<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\fields\CommerceProducts as FeedMeProducts;

class Products extends FeedMeProducts
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static $name = 'Products';
    public static $class = 'verbb\formie\fields\formfields\Products';

}
