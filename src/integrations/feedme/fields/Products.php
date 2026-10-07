<?php
namespace verbb\formie\integrations\feedme\fields;

use verbb\formie\fields\Products as ProductsField;

use craft\feedme\fields\CommerceProducts as FeedMeProducts;

class Products extends FeedMeProducts
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static string $class = ProductsField::class;
    public static string $name = 'Products';


    // Public Methods
    // =========================================================================

    // Templates
    // =========================================================================

    public function getMappingTemplate(): string
    {
        return 'formie/integrations/feedme/fields/products';
    }
}
