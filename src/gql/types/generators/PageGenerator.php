<?php
namespace verbb\formie\gql\types\generators;

use verbb\formie\gql\interfaces\PageInterface;
use verbb\formie\gql\types\PageType;

use Craft;
use craft\gql\base\GeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\TypeLoader;
use craft\gql\TypeManager;
use craft\helpers\Gql as GqlHelper;

class PageGenerator implements GeneratorInterface
{
    // Public Methods
    // =========================================================================

    public static function generateTypes($context = null): array
    {
        $gqlTypes = [];

        $typeName = self::getName();

        $fieldFields = PageInterface::getFieldDefinitions();

        $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new PageType([
            'name' => $typeName,
            'fields' => function() use ($fieldFields) {
                return $fieldFields;
            }
        ]));

        return $gqlTypes;
    }

    public static function getName($context = null): string
    {
        return 'PageType';
    }
}
