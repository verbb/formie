<?php
namespace verbb\formie\gql\types\generators;

use verbb\formie\gql\types\FieldAttributeType;

use craft\gql\base\GeneratorInterface;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;

class FieldAttributeGenerator implements GeneratorInterface, SingleGeneratorInterface
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function generateTypes(mixed $context = null): array
    {
        return [static::generateType($context)];
    }

    public static function getName($context = null): string
    {
        return 'FieldAttribute';
    }

    /**
     * @inheritdoc
     */
    public static function generateType(mixed $context = null): mixed
    {
        $typeName = self::getName($context);
        $contentFields = FieldAttributeType::prepareRowFieldDefinition($typeName);

        return GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new FieldAttributeType([
            'name' => $typeName,
            'fields' => function() use ($contentFields) {
                return $contentFields;
            },
        ]));
    }
}
