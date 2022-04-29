<?php
namespace verbb\formie\gql\types\generators;

use Craft;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class FieldOptionGenerator implements GeneratorInterface, SingleGeneratorInterface
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
        return 'FieldOption';
    }

    /**
     * @inheritdoc
     */
    public static function generateType(mixed $context = null): mixed
    {
        $typeName = self::getName($context);

        return GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new ObjectType([
            'name' => $typeName,
            'fields' => Craft::$app->getGql()->prepareFieldDefinitions([
                'label' => [
                    'name' => 'label',
                    'type' => Type::string(),
                    'description' => 'The label of the option.',
                    'resolve' => function($source) {
                        // Optgroups are handled differently, so normalize
                        return isset($source['optgroup']) ? $source['optgroup'] : $source['label'];
                    },
                ],

                'value' => [
                    'name' => 'value',
                    'type' => Type::string(),
                    'description' => 'The value of the option.',
                    'resolve' => function($source) {
                        // Optgroups are handled differently, so normalize
                        return isset($source['optgroup']) ? '' : $source['value'];
                    },
                ],

                'isOptgroup' => [
                    'name' => 'isOptgroup',
                    'type' => Type::boolean(),
                    'description' => 'Whether this option has been marked as an `optgroup`.',
                    'resolve' => function($source) {
                        // Optgroups are handled differently, so normalize
                        return isset($source['optgroup']);
                    },
                ],

                'isDefault' => [
                    'name' => 'isDefault',
                    'type' => Type::boolean(),
                    'description' => 'Whether this option has been marked as a default.',
                    'resolve' => function($source) {
                        // Optgroups are handled differently, so normalize
                        return isset($source['optgroup']) ? false : $source['isDefault'];
                    },
                ],
            ], $typeName),
        ]));
    }
}
