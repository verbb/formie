<?php
namespace verbb\formie\gql\interfaces;

use verbb\formie\fields\formfields\Table;
use verbb\formie\gql\types\generators\FieldGenerator;
use verbb\formie\gql\types\generators\FieldAttributeGenerator;

use Craft;
use craft\gql\base\InterfaceType as BaseInterfaceType;
use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class FieldInterface extends BaseInterfaceType
{
    // Static Methods
    // =========================================================================

    public static function getTypeGenerator(): string
    {
        return FieldGenerator::class;
    }

    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all fields.',
            'resolveType' => function($value) {
                return $value->getGqlTypeName();
            },
        ]));

        FieldGenerator::generateTypes();

        return $type;
    }

    public static function getName(): string
    {
        return 'FieldInterface';
    }

    public static function getFieldDefinitions(): array
    {
        return Craft::$app->getGql()->prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'name' => [
                'name' => 'name',
                'type' => Type::string(),
                'description' => 'The field’s name.',
            ],
            'handle' => [
                'name' => 'handle',
                'type' => Type::string(),
                'description' => 'The field’s handle.',
            ],
            'instructions' => [
                'name' => 'instructions',
                'type' => Type::string(),
                'description' => 'The field’s instructions.',
            ],
            'required' => [
                'name' => 'required',
                'type' => Type::boolean(),
                'description' => 'Whether the field is required.',
            ],
            'type' => [
                'name' => 'type',
                'type' => Type::string(),
                'description' => 'The field’s type.',
                'resolve' => function($field) {
                    return get_class($field);
                },
            ],
            'displayName' => [
                'name' => 'displayName',
                'type' => Type::string(),
                'description' => 'The field’s display name.',
                'resolve' => function($field) {
                    $classNameParts = explode('\\', get_class($field));

                    return array_pop($classNameParts);
                },
            ],
            'typeName' => [
                'name' => 'typeName',
                'type' => Type::string(),
                'description' => 'The field’s GQL type.',
                'resolve' => function($field) {
                    return $field->getGqlTypeName();
                },
            ],
            'inputTypeName' => [
                'name' => 'inputTypeName',
                'type' => Type::string(),
                'description' => 'The field’s GQL input name.',
                'resolve' => function($field) {
                    $inputType = $field->getContentGqlMutationArgumentType();

                    // Table fields don't seem to resolve correctly?
                    if ($field instanceof Table) {
                        return '[' . $inputType->name . ']';
                    }

                    if (is_string($inputType)) {
                        return $inputType;
                    }

                    if (is_array($inputType)) {
                        return $inputType['type'];
                    }

                    return $inputType;
                },
            ],
            'limit' => [
                'name' => 'limit',
                'type' => Type::boolean(),
                'description' => 'Whether the field should limit content.',
            ],
            'limitType' => [
                'name' => 'limitType',
                'type' => Type::string(),
                'description' => 'The field’s limit type.',
            ],
            'limitAmount' => [
                'name' => 'limitAmount',
                'type' => Type::int(),
                'description' => 'The field’s limit amount.',
            ],
            'placeholder' => [
                'name' => 'placeholder',
                'type' => Type::string(),
                'description' => 'The field’s placeholder.',
            ],
            'errorMessage' => [
                'name' => 'errorMessage',
                'type' => Type::string(),
                'description' => 'The field’s error message.',
            ],
            'labelPosition' => [
                'name' => 'labelPosition',
                'type' => Type::string(),
                'description' => 'The field’s label position.',
            ],
            'instructionsPosition' => [
                'name' => 'instructionsPosition',
                'type' => Type::string(),
                'description' => 'The field’s instructions position.',
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
            'includeInEmail' => [
                'name' => 'includeInEmail',
                'type' => Type::boolean(),
                'description' => 'Whether the field should be included in email content.',
            ],
            'enableConditions' => [
                'name' => 'enableConditions',
                'type' => Type::boolean(),
                'description' => 'Whether the field has conditions enabled.',
            ],
            'conditions' => [
                'name' => 'conditions',
                'type' => Type::string(),
                'description' => 'The field’s conditions.',
            ],
            'enableContentEncryption' => [
                'name' => 'enableContentEncryption',
                'type' => Type::boolean(),
                'description' => 'Whether the field has content encryption enabled.',
            ],
            'visibility' => [
                'name' => 'visibility',
                'type' => Type::string(),
                'description' => 'The field’s visibility.',
            ],
        ]), self::getName());
    }
}
