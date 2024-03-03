<?php
namespace verbb\formie\gql\types\input;

use verbb\formie\fields\Group as GroupField;

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

        foreach ($context->getFields() as $field) {
            $groupFields[$field->handle] = $field->getContentGqlMutationArgumentType();
        }

        return GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => function() use ($groupFields) {
                return $groupFields;
            },
        ]));
    }
}
