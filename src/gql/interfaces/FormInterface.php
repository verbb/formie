<?php
namespace verbb\formie\gql\interfaces;

use verbb\formie\elements\Form;
use verbb\formie\gql\types\FormSettingsType;
use verbb\formie\gql\types\generators\FormGenerator;

use Craft;
use craft\gql\interfaces\Element;
use craft\gql\GqlEntityRegistry;

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
        return Craft::$app->getGql()->prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
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
                'args' => [
                    'includeDisabled' => [
                        'name' => 'includeDisabled',
                        'description' => 'Whether to include fields with visibility "disabled".',
                        'type' => Type::boolean(),
                    ],
                ],
                'resolve' => function($source, $arguments) {
                    $includeDisabled = $arguments['includeDisabled'] ?? false;

                    return $source->getRows($includeDisabled);
                },
            ],
            'formFields' => [
                'name' => 'formFields',
                'type' => Type::listOf(FieldInterface::getType()),
                'description' => 'The form’s fields.',
                'args' => [
                    'includeDisabled' => [
                        'name' => 'includeDisabled',
                        'description' => 'Whether to include fields with visibility "disabled".',
                        'type' => Type::boolean(),
                    ],
                ],
                'resolve' => function($source, $arguments) {
                    $includeDisabled = $arguments['includeDisabled'] ?? false;

                    return $source->getFields($includeDisabled);
                },
            ],
            'settings' => [
                'name' => 'settings',
                'type' => FormSettingsType::getType(),
                'description' => 'The form’s settings.',
            ],
            'isAvailable' => [
                'name' => 'isAvailable',
                'type' => Type::boolean(),
                'description' => 'Whether the form is considered available according to user checks, scheduling and more.',
                'resolve' => function ($source) {
                    return $source->isAvailable();
                },
            ],
        ]), self::getName());
    }
}
