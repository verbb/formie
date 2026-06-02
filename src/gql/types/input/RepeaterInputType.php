<?php
namespace verbb\formie\gql\types\input;

use verbb\formie\base\Field;
use verbb\formie\Formie;
use verbb\formie\fields\Repeater as RepeaterField;

use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class RepeaterInputType extends InputObjectType
{
    // Static Methods
    // =========================================================================

    public static function getType(RepeaterField $context): mixed
    {
        /** @var RepeaterField $context */
        $typeName = $context->getForm()->handle . '_' . $context->handle . '_FormieRepeaterInput';

        if ($inputType = GqlEntityRegistry::getEntity($typeName)) {
            return $inputType;
        }

        $repeaterFields = [];
        $fieldsService = Formie::$plugin->getFields();

        foreach ($context->getFields() as $field) {
            $repeaterFields[$field->handle] = $fieldsService->getFieldContentGqlMutationArgumentType($field);
        }

        // All the different field block types now get wrapped in a container input.
        // If two different block types are passed, the selected block type to parse is undefined.
        $rowContainerTypeName = $context->getForm()->handle . '_' . $context->handle . '_RepeaterRowInput';
        $rowContainerType = GqlEntityRegistry::createEntity($rowContainerTypeName, new InputObjectType([
            'name' => $rowContainerTypeName,
            'fields' => function() use ($repeaterFields) {
                return $repeaterFields;
            },
        ]));

        return GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => function() use ($rowContainerType) {
                return [
                    'rows' => Type::listOf($rowContainerType),
                ];
            },
        ]));
    }

    public static function getTypeFromConfig(array $config): mixed
    {
        $fieldsService = Formie::$plugin->getFields();
        $typeName = $fieldsService->getFieldConfigGqlTypeName($config, 'RepeaterInput');

        if ($inputType = GqlEntityRegistry::getEntity($typeName)) {
            return $inputType;
        }

        $repeaterFields = [];

        foreach ($fieldsService->getNestedFieldConfigs($config) as $fieldConfig) {
            $handle = $fieldConfig['handle'] ?? null;

            if (!$handle) {
                continue;
            }

            $repeaterFields[$handle] = $fieldsService->getFieldConfigContentGqlMutationArgumentType($fieldConfig);
        }

        $rowContainerTypeName = $fieldsService->getFieldConfigGqlTypeName($config, 'RepeaterRowInput');
        $rowContainerType = GqlEntityRegistry::createEntity($rowContainerTypeName, new InputObjectType([
            'name' => $rowContainerTypeName,
            'fields' => fn() => $repeaterFields,
        ]));

        return GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => fn() => [
                'rows' => Type::listOf($rowContainerType),
            ],
        ]));
    }
}
