<?php
namespace verbb\formie\gql\types\generators;

use verbb\formie\gql\interfaces\RowInterface;
use verbb\formie\gql\types\RowType;

use craft\gql\base\GeneratorInterface;
use craft\gql\GqlEntityRegistry;

class RowGenerator implements GeneratorInterface
{
    // Static Methods
    // =========================================================================

    public static function generateTypes(mixed $context = null): array
    {
        $gqlTypes = [];

        $typeName = self::getName();

        $fieldFields = RowInterface::getFieldDefinitions();

        $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new RowType([
            'name' => $typeName,
            'fields' => function() use ($fieldFields) {
                return $fieldFields;
            },
        ]));

        return $gqlTypes;
    }

    public static function getName($context = null): string
    {
        return 'RowType';
    }
}
