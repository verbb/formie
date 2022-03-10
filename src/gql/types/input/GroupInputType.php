<?php
namespace verbb\formie\gql\types\input;

use verbb\formie\fields\formfields\Group as GroupField;

use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\InputObjectType;

class GroupInputType extends InputObjectType
{
    // Static Methods
    // =========================================================================

    /**
     * Create the type for a Group form field
     *
     * @param GroupField $context
     * @return bool|mixed
     */
    public static function getType(GroupField $context): mixed
    {
        /** @var GroupField $context */
        $typeName = $context->getForm()->handle . '_' . $context->handle . '_FormieGroupInput';

        if ($inputType = GqlEntityRegistry::getEntity($typeName)) {
            return $inputType;
        }

        // Array of block types.
        $fields = $context->getCustomFields();

        $groupFields = [];
        foreach ($fields as $field) {
            $field->isNested = true;
            $field->setContainer($context);

            $fieldInput = $field->getContentGqlMutationArgumentType();
            $groupFields[$field->handle] = $fieldInput;
        }

        return GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => function() use ($groupFields) {
                return $groupFields;
            },
            'normalizeValue' => [self::class, 'normalizeValue'],
        ]));
    }

    /**
     * Normalize input data to what Formie expects.
     *
     * @param $value
     * @return mixed
     */
    public static function normalizeValue($value): mixed
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
