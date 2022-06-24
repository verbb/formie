<?php
namespace verbb\formie\gql\types\generators;

use verbb\formie\gql\types\FieldAttributeType;

use craft\gql\base\GeneratorInterface;
use craft\gql\base\ObjectType;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;

class FieldAttributeGenerator implements GeneratorInterface, SingleGeneratorInterface
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function generateTypes($context = null): array
    {
        return [static::generateType($context)];
    }

    /**
     * @inheritdoc
     */
    public static function getName($context = null): string
    {
        return 'FieldAttribute';
    }

    /**
     * @inheritdoc
     */
    public static function generateType($context = null): ObjectType
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
