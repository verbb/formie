<?php
namespace verbb\formie\gql\types\input;

use verbb\formie\fields\formfields\Group as GroupField;

use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\InputObjectType;

class GroupInputType extends InputObjectType
{
    /**
     * Create the type for a Group form field
     *
     * @param $context
     * @return bool|mixed
     */
    public static function getType(GroupField $context)
    {
        /** @var GroupField $context */
        $typeName = $context->getForm()->handle . '_' . $context->handle . '_FormieGroupInput';

        if ($inputType = GqlEntityRegistry::getEntity($typeName)) {
            return $inputType;
        }

        // Array of block types.
        $fields = $context->getFields();

        $groupFields = [];
        foreach ($fields as $field) {
            $field->isNested = true;
            $field->setContainer($context);

            $fieldInput = $field->getContentGqlMutationArgumentType();
            $groupFields[$field->handle] = $fieldInput;
        }

        $inputType = GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => function() use ($groupFields) {
                return $groupFields;
            },
            'normalizeValue' => [self::class, 'normalizeValue'],
        ]));

        return $inputType;
    }

    /**
     * Normalize input data to what Formie expects.
     *
     * @param $value
     * @return mixed
     */
    public static function normalizeValue($value)
    {
        return [
            'rows' => [
                'new1' => [
                    'fields' => $value,
                ],
            ],
            'sortOrder' => ['new1'],
        ];
    }
}
