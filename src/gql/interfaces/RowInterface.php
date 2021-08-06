<?php
namespace verbb\formie\gql\interfaces;

use verbb\formie\gql\types\generators\RowGenerator;
use verbb\formie\models\Row;
use verbb\formie\gql\interfaces\FieldInterface;

use craft\gql\base\InterfaceType as BaseInterfaceType;
use craft\gql\interfaces\Element;
use craft\gql\types\DateTime;
use craft\gql\TypeLoader;
use craft\gql\TypeManager;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Gql;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class RowInterface extends BaseInterfaceType
{
    // Public Methods
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
        return TypeManager::prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'fields' => [
                'name' => 'fields',
                'type' => Type::listOf(FieldInterface::getType()),
                'description' => 'The row’s fields.',
            ],
        ]), self::getName());
    }
}
