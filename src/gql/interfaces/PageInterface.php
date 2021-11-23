<?php
namespace verbb\formie\gql\interfaces;

use verbb\formie\gql\types\generators\PageGenerator;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\gql\interfaces\FieldInterface;
use verbb\formie\gql\interfaces\RowInterface;
use verbb\formie\gql\interfaces\PageSettingsInterface;

use craft\gql\base\InterfaceType as BaseInterfaceType;
use craft\gql\interfaces\Element;
use craft\gql\types\DateTime;
use craft\gql\TypeLoader;
use craft\gql\TypeManager;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Gql;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class PageInterface extends BaseInterfaceType
{
    // Public Methods
    // =========================================================================

    public static function getTypeGenerator(): string
    {
        return PageGenerator::class;
    }

    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all pages.',
            'resolveType' => function(FieldLayoutPage $value) {
                return GqlEntityRegistry::getEntity(PageGenerator::getName());
            },
        ]));

        PageGenerator::generateTypes();

        return $type;
    }

    public static function getName(): string
    {
        return 'PageInterface';
    }

    public static function getFieldDefinitions(): array
    {
        $fields = array_merge(parent::getFieldDefinitions(), [
            'name' => [
                'name' => 'name',
                'type' => Type::string(),
                'description' => 'The page’s name.',
            ],
            'rows' => [
                'name' => 'rows',
                'type' => Type::listOf(RowInterface::getType()),
                'description' => 'The page’s rows.',
            ],
            'fields' => [
                'name' => 'fields',
                'type' => Type::listOf(FieldInterface::getType()),
                'description' => 'The page’s fields.',
            ],
            'settings' => [
                'name' => 'settings',
                'type' => PageSettingsInterface::getType(),
                'description' => 'The page’s settings.',
            ],
        ]);
        
        return TypeManager::prepareFieldDefinitions($fields, self::getName());
    }
}
