<?php
namespace verbb\formie\gql\interfaces;

use verbb\formie\gql\types\generators\PageSettingsGenerator;
use verbb\formie\models\PageSettings;

use craft\gql\base\InterfaceType as BaseInterfaceType;
use craft\gql\interfaces\Element;
use craft\gql\types\DateTime;
use craft\gql\TypeLoader;
use craft\gql\TypeManager;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Gql;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class PageSettingsInterface extends BaseInterfaceType
{
    // Public Methods
    // =========================================================================

    public static function getTypeGenerator(): string
    {
        return PageSettingsGenerator::class;
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
            'resolveType' => function(PageSettings $value) {
                return GqlEntityRegistry::getEntity(PageSettingsGenerator::getName());
            },
        ]));

        PageSettingsGenerator::generateTypes();

        return $type;
    }

    public static function getName(): string
    {
        return 'PageSettingsInterface';
    }

    public static function getFieldDefinitions(): array
    {
        return TypeManager::prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'submitButtonLabel' => [
                'name' => 'submitButtonLabel',
                'type' => Type::string(),
                'description' => 'The page’s submit button label.'
            ],
            'backButtonLabel' => [
                'name' => 'backButtonLabel',
                'type' => Type::string(),
                'description' => 'The page’s submit button label.'
            ],
            'showBackButton' => [
                'name' => 'showBackButton',
                'type' => Type::boolean(),
                'description' => 'The page’s submit button label.'
            ],
            'buttonsPosition' => [
                'name' => 'buttonsPosition',
                'type' => Type::string(),
                'description' => 'The page’s submit button label.'
            ],
        ]), self::getName());
    }
}
