<?php
namespace verbb\formie\gql\types\input;

use verbb\formie\fields\Group as GroupField;
use verbb\formie\Formie;

use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\InputObjectType;

class GroupInputType extends InputObjectType
{
    // Static Methods
    // =========================================================================

    public static function getType(GroupField $context): mixed
    {
        /** @var GroupField $context */
        $typeName = $context->getForm()->handle . '_' . $context->handle . '_FormieGroupInput';

        if ($inputType = GqlEntityRegistry::getEntity($typeName)) {
            return $inputType;
        }

        $groupFields = [];
        $fieldsService = Formie::$plugin->getFields();

        foreach ($context->getFields() as $field) {
            $groupFields[$field->handle] = $fieldsService->getFieldContentGqlMutationArgumentType($field);
        }

        return GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => function() use ($groupFields) {
                return $groupFields;
            },
        ]));
    }

    public static function getTypeFromConfig(array $config): mixed
    {
        $fieldsService = Formie::$plugin->getFields();
        $typeName = $fieldsService->getFieldConfigGqlTypeName($config, 'GroupInput');

        if ($inputType = GqlEntityRegistry::getEntity($typeName)) {
            return $inputType;
        }

        $groupFields = [];

        foreach ($fieldsService->getNestedFieldConfigs($config) as $fieldConfig) {
            $handle = $fieldConfig['handle'] ?? null;

            if (!$handle) {
                continue;
            }

            $groupFields[$handle] = $fieldsService->getFieldConfigContentGqlMutationArgumentType($fieldConfig);
        }

        return GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => fn() => $groupFields,
        ]));
    }
}
