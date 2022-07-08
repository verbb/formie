<?php
namespace verbb\formie\gql\interfaces;

use verbb\formie\gql\types\generators\FieldAttributeGenerator;
use verbb\formie\gql\types\generators\PageSettingsGenerator;
use verbb\formie\models\PageSettings;

use Craft;
use craft\gql\base\InterfaceType as BaseInterfaceType;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Json;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class PageSettingsInterface extends BaseInterfaceType
{
    // Static Methods
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
        $fields = [
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
            'saveButtonLabel' => [
                'name' => 'saveButtonLabel',
                'type' => Type::string(),
                'description' => 'The page’s draft button label.',
            ],
            'showSaveButton' => [
                'name' => 'showSaveButton',
                'type' => Type::boolean(),
                'description' => 'Whether to show the page’s draft button.',
            ],
            'buttonsPosition' => [
                'name' => 'buttonsPosition',
                'type' => Type::string(),
                'description' => 'The page’s button (back and submit) positions.',
            ],
            'cssClasses' => [
                'name' => 'cssClasses',
                'type' => Type::string(),
                'description' => 'The page’s button (back and submit) CSS classes.',
            ],
            'containerAttributes' => [
                'name' => 'containerAttributes',
                'type' => Type::listOf(FieldAttributeGenerator::generateType()),
                'description' => 'The page’s button (back and submit) container attributes.',
            ],
            'inputAttributes' => [
                'name' => 'inputAttributes',
                'type' => Type::listOf(FieldAttributeGenerator::generateType()),
                'description' => 'The page’s button (back and submit) input attributes.',
            ],
            'enablePageConditions' => [
                'name' => 'enablePageConditions',
                'type' => Type::boolean(),
                'description' => 'Whether the page has conditions enabled.',
            ],
            'pageConditions' => [
                'name' => 'pageConditions',
                'type' => Type::string(),
                'description' => 'The page’s conditions as a JSON string.',
                'resolve' => function($source) {
                    $value = $source->pageConditions;

                    return is_array($value) ? Json::encode($value) : $value;
                },
            ],
            'enableNextButtonConditions' => [
                'name' => 'enableNextButtonConditions',
                'type' => Type::boolean(),
                'description' => 'Whether the page’s next button has conditions enabled, for multi-page forms.',
            ],
            'nextButtonConditions' => [
                'name' => 'nextButtonConditions',
                'type' => Type::string(),
                'description' => 'The page’s conditions for whether to show the next button, for multi-page forms as a JSON string.',
                'resolve' => function($source) {
                    $value = $source->nextButtonConditions;

                    return is_array($value) ? Json::encode($value) : $value;
                },
            ],
        ];

        return Craft::$app->getGql()->prepareFieldDefinitions($fields, self::getName());
    }
}
