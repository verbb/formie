<?php
namespace verbb\formie\gql\interfaces;

use verbb\formie\gql\types\generators\FieldAttributeGenerator;
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
        $fields = array_merge(parent::getFieldDefinitions(), [
            'submitButtonLabel' => [
                'name' => 'submitButtonLabel',
                'type' => Type::string(),
                'description' => 'The page’s submit button label.',
            ],
            'backButtonLabel' => [
                'name' => 'backButtonLabel',
                'type' => Type::string(),
                'description' => 'The page’s back button label.',
            ],
            'showBackButton' => [
                'name' => 'showBackButton',
                'type' => Type::boolean(),
                'description' => 'Whether to show the page’s back button.',
            ],
            'buttonsPosition' => [
                'name' => 'buttonsPosition',
                'type' => Type::string(),
                'description' => 'The page’s button positions.',
            ],
            'cssClasses' => [
                'name' => 'cssClasses',
                'type' => Type::string(),
                'description' => 'The field’s CSS classes.',
            ],
            'containerAttributes' => [
                'name' => 'containerAttributes',
                'type' => Type::listOf(FieldAttributeGenerator::generateType()),
                'description' => 'The field’s container attributes.',
            ],
            'inputAttributes' => [
                'name' => 'inputAttributes',
                'type' => Type::listOf(FieldAttributeGenerator::generateType()),
                'description' => 'The field’s input attributes.',
            ],
            'enablePageConditions' => [
                'name' => 'enablePageConditions',
                'type' => Type::boolean(),
                'description' => 'Whether the page has conditions enabled.',
            ],
            'pageConditions' => [
                'name' => 'pageConditions',
                'type' => Type::listOf(Type::string()),
                'description' => 'The page’s conditions.',
            ],
            'enableNextButtonConditions' => [
                'name' => 'enableNextButtonConditions',
                'type' => Type::boolean(),
                'description' => 'Whether the page has conditions enabled.',
            ],
            'nextButtonConditions' => [
                'name' => 'nextButtonConditions',
                'type' => Type::listOf(Type::string()),
                'description' => 'The page’s conditions.',
            ],
        ]);
        unset($fields['id'], $fields['uid']);
        return TypeManager::prepareFieldDefinitions($fields, self::getName());
    }
}
