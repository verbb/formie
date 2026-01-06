<?php
namespace verbb\formie\gql\interfaces;

use verbb\formie\gql\types\Json as JsonType;
use verbb\formie\gql\types\generators\FieldAttributeGenerator;
use verbb\formie\gql\types\generators\PageSettingsGenerator;
use verbb\formie\models\FieldLayoutPageSettings;

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
            'resolveType' => function(FieldLayoutPageSettings $value) {
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
                'type' => JsonType::getType(),
                'description' => 'The page’s conditions as a JSON string.',
            ],
            'enableNextButtonConditions' => [
                'name' => 'enableNextButtonConditions',
                'type' => Type::boolean(),
                'description' => 'Whether the page’s next button has conditions enabled, for multi-page forms.',
            ],
            'nextButtonConditions' => [
                'name' => 'nextButtonConditions',
                'type' => JsonType::getType(),
                'description' => 'The page’s conditions for whether to show the next button, for multi-page forms as a JSON string.',
            ],
            'enableJsEvents' => [
                'name' => 'enableJsEvents',
                'type' => Type::boolean(),
                'description' => 'Whether the page’s button has JS events enabled.',
            ],
            'jsGtmEventOptions' => [
                'name' => 'jsGtmEventOptions',
                'type' => JsonType::getType(),
                'description' => 'The page’s JS event options (GTM) as a JSON string.',
            ],
        ];

        return Craft::$app->getGql()->prepareFieldDefinitions($fields, self::getName());
    }
}
