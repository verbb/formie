<?php
namespace verbb\formie\integrations\feedme\fields;

use verbb\formie\fields\Categories as CategoriesField;

use craft\feedme\fields\Categories as FeedMeCategories;

class Categories extends FeedMeCategories
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static string $class = CategoriesField::class;
    public static string $name = 'Categories';


    // Templates
    // =========================================================================

    public function getMappingTemplate(): string
    {
        return 'formie/integrations/feedme/fields/categories';
    }

}
