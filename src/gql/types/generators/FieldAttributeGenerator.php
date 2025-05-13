<?php
namespace verbb\formie\gql\types\generators;

use Craft;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class FieldAttributeGenerator implements GeneratorInterface, SingleGeneratorInterface
{
    // Static Methods
    // =========================================================================

    public static function generateTypes(mixed $context = null): array
    {
        return [static::generateType($context)];
    }

    public static function getName($context = null): string
    {
        return 'FieldAttribute';
    }

    public static function generateType(mixed $context = null): mixed
    {
        $typeName = self::getName($context);

        return GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new ObjectType([
            'name' => $typeName,
            'fields' => Craft::$app->getGql()->prepareFieldDefinitions([
                'label' => [
                    'name' => 'label',
                    'type' => Type::string(),
                    'description' => 'The label attribute.',
                ],

                'value' => [
                    'name' => 'value',
                    'type' => Type::string(),
                    'description' => 'The value attribute.',
                ],
            ], $typeName),
        ]));
    }
}
