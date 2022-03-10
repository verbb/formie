<?php
namespace verbb\formie\gql\types\generators;

use verbb\formie\gql\interfaces\PageSettingsInterface;
use verbb\formie\gql\types\PageSettingsType;

use craft\gql\base\GeneratorInterface;
use craft\gql\GqlEntityRegistry;

class PageSettingsGenerator implements GeneratorInterface
{
    // Static Methods
    // =========================================================================

    public static function generateTypes(mixed $context = null): array
    {
        $gqlTypes = [];

        $typeName = self::getName();

        $fieldFields = PageSettingsInterface::getFieldDefinitions();

        $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new PageSettingsType([
            'name' => $typeName,
            'fields' => function() use ($fieldFields) {
                return $fieldFields;
            },
        ]));

        return $gqlTypes;
    }

    public static function getName($context = null): string
    {
        return 'PageSettingsType';
    }
}
