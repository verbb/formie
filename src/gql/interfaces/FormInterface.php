<?php
namespace verbb\formie\gql\interfaces;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\gql\types\CsrfTokenType;
use verbb\formie\gql\types\FormSettingsType;
use verbb\formie\gql\types\generators\FormGenerator;

use Craft;
use craft\gql\interfaces\Element;
use craft\gql\TypeManager;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Json;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class FormInterface extends Element
{
    // Static Methods
    // =========================================================================

    public static function getTypeGenerator(): string
    {
        return FormGenerator::class;
    }

    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all forms.',
            'resolveType' => function(Form $value) {
                return $value->getGqlTypeName();
            },
        ]));

        FormGenerator::generateTypes();

        return $type;
    }

    public static function getName(): string
    {
        return 'FormInterface';
    }

    public static function getFieldDefinitions(): array
    {
        $formFieldName = Formie::$plugin->getSettings()->enableGatsbyCompatibility ? 'formFields' : 'fields';

        return TypeManager::prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'handle' => [
                'name' => 'handle',
                'type' => Type::string(),
                'description' => 'The form’s handle.',
            ],
            'pages' => [
                'name' => 'pages',
                'type' => Type::listOf(PageInterface::getType()),
                'description' => 'The form’s pages.',
            ],
            'rows' => [
                'name' => 'rows',
                'type' => Type::listOf(RowInterface::getType()),
                'description' => 'The form’s rows.',
            ],
            $formFieldName => [
                'name' => $formFieldName,
                'type' => Type::listOf(FieldInterface::getType()),
                'description' => 'The form’s fields.',
            ],
            'settings' => [
                'name' => 'settings',
                'type' => FormSettingsType::getType(),
                'description' => 'The form’s settings.',
            ],
            'configJson' => [
                'name' => 'configJson',
                'type' => Type::string(),
                'description' => 'The form’s config as JSON.',
            ],
            'templateHtml' => [
                'name' => 'templateHtml',
                'type' => Type::string(),
                'description' => 'The form’s rendered HTML.',
                'args' => [
                    'options' => [
                        'name' => 'options',
                        'description' => 'The form template HTML will be rendered with these JSON serialized options.',
                        'type' => Type::string(),
                    ],
                    'populateFormValues' => [
                        'name' => 'populateFormValues',
                        'description' => 'The form field values will be populated with these JSON serialized options.',
                        'type' => Type::string(),
                    ],
                ],
                'resolve' => function($source, $arguments) {
                    $options = Json::decodeIfJson($arguments['options'] ?? null);
                    $populateFormValues = Json::decodeIfJson($arguments['populateFormValues'] ?? null);

                    if ($populateFormValues) {
                        Formie::$plugin->getRendering()->populateFormValues($source, $populateFormValues);
                    }

                    return Formie::$plugin->getRendering()->renderForm($source, $options);
                },
            ],
            'csrfToken' => [
                'name' => 'csrfToken',
                'type' => CsrfTokenType::getType(),
                'description' => 'A CSRF token (name and value)',
                'resolve' => function() {
                    if (!Craft::$app->getConfig()->getGeneral()->enableCsrfProtection) {
                        return null;
                    }

                    return [
                        'name' => Craft::$app->getRequest()->csrfParam,
                        'value' => Craft::$app->getRequest()->getCsrfToken(),
                    ];
                },
            ],
        ]), self::getName());
    }
}
