<?php
namespace verbb\formie\gql\types\generators;

use verbb\formie\gql\interfaces\PageInterface;
use verbb\formie\gql\types\PageType;

use craft\gql\base\GeneratorInterface;
use craft\gql\GqlEntityRegistry;

class PageGenerator implements GeneratorInterface
{
    // Static Methods
    // =========================================================================

    public static function generateTypes(mixed $context = null): array
    {
        $gqlTypes = [];

        $typeName = self::getName();

        $fieldFields = PageInterface::getFieldDefinitions();

        $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new PageType([
            'name' => $typeName,
            'fields' => function() use ($fieldFields) {
                return $fieldFields;
            },
        ]));

        return $gqlTypes;
    }

    public static function getName($context = null): string
    {
        return 'PageType';
    }
}
