<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\fields\CommerceProducts as FeedMeProducts;
use verbb\formie\fields\formfields\Products as ProductsField;

class Products extends FeedMeProducts
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;

    
    // Properties
    // =========================================================================

    public static $name = 'Products';
    public static $class = ProductsField::class;

}
