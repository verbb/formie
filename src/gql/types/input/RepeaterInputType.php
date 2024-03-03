<?php
namespace verbb\formie\gql\types\input;

use verbb\formie\base\Field;
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

        foreach ($context->getFields() as $field) {
            $repeaterFields[$field->handle] = $field->getContentGqlMutationArgumentType();
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
}
