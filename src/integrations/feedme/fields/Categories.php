<?php
namespace verbb\formie\integrations\feedme\fields;

use verbb\formie\fields\formfields\Categories as CategoriesField;

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

}
