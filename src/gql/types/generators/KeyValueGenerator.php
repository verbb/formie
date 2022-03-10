<?php
namespace verbb\formie\gql\types\generators;

use verbb\formie\gql\types\KeyValueType;

use Craft;
use craft\gql\base\GeneratorInterface;
use craft\gql\GqlEntityRegistry;

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

        $contentFields = Craft::$app->getGql()->prepareFieldDefinitions($contentFields, $typeName);

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
