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

        $groupFields = [];

        foreach ($context->getCustomFields() as $field) {
            $field->isNested = true;
            $field->setContainer($context);

            $groupFields[$field->handle] = $field->getContentGqlMutationArgumentType();
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
    public static function normalizeValue(mixed $value): mixed
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
