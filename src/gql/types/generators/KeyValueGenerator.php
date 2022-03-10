<?php
namespace verbb\formie\gql\types\generators;

use verbb\formie\gql\types\KeyValueType;

use craft\gql\base\GeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\TypeManager;

class KeyValueGenerator implements GeneratorInterface
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function generateTypes(mixed $context = null, $contentFields = []): array
    {
        $typeName = self::getName();

        $contentFields = TypeManager::prepareFieldDefinitions($contentFields, $typeName);

        $type = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new KeyValueType([
            'name' => $typeName,
            'fields' => function() use ($contentFields) {
                return $contentFields;
            },
        ]));

        return [$type];
    }

    public static function getName($context = null): string
    {
        return 'KeyValueType';
    }
}
