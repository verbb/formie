<?php
namespace verbb\formie\gql\types\input;

use verbb\formie\base\FormField;
use verbb\formie\fields\formfields\Repeater as RepeaterField;

use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class RepeaterInputType extends InputObjectType
{
    // Static Methods
    // =========================================================================

    /**
     * Create the type for a Repeater form field
     *
     * @param RepeaterField $context
     * @return bool|mixed
     */
    public static function getType(RepeaterField $context): mixed
    {
        /** @var RepeaterField $context */
        $typeName = $context->getForm()->handle . '_' . $context->handle . '_FormieRepeaterInput';

        if ($inputType = GqlEntityRegistry::getEntity($typeName)) {
            return $inputType;
        }

        $repeaterFields = [];

        foreach ($context->getCustomFields() as $field) {
            $field->isNested = true;
            $field->setContainer($context);

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
        $preparedRows = [];
        $rowCounter = 1;

        if (!empty($value['rows'])) {
            foreach ($value['rows'] as $row) {
                if (!empty($row)) {
                    $key = 'new' . $rowCounter++;
                    $sortOrder[] = $key;
                    $preparedRows[$key] = [
                        'fields' => $row,
                    ];
                }
            }
        }

        return [
            'rows' => $preparedRows,
            'sortOrder' => $sortOrder ?? [],
        ];
    }
}
