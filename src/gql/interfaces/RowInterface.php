<?php
namespace verbb\formie\gql\interfaces;

use verbb\formie\gql\types\generators\RowGenerator;

use Craft;
use craft\gql\base\InterfaceType as BaseInterfaceType;
use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class RowInterface extends BaseInterfaceType
{
    // Static Methods
    // =========================================================================

    public static function getTypeGenerator(): string
    {
        return RowGenerator::class;
    }

    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all rows.',
            'resolveType' => function($value) {
                return GqlEntityRegistry::getEntity(RowGenerator::getName());
            },
        ]));

        RowGenerator::generateTypes();

        return $type;
    }

    public static function getName(): string
    {
        return 'RowInterface';
    }

    public static function getFieldDefinitions(): array
    {
        return Craft::$app->getGql()->prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'rowFields' => [
                'name' => 'rowFields',
                'type' => Type::listOf(FieldInterface::getType()),
                'description' => 'The row’s fields.',
                'args' => [
                    'includeDisabled' => [
                        'name' => 'includeDisabled',
                        'description' => 'Whether to include fields with visibility "disabled".',
                        'type' => Type::boolean(),
                    ],
                ],
            ],
        ]), self::getName());
    }
}
