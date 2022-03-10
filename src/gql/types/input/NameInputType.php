<?php
namespace verbb\formie\gql\types\input;

use verbb\formie\fields\formfields\Name as NameField;
use verbb\formie\models\Name as NameModel;

use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class NameInputType extends InputObjectType
{
    // Static Methods
    // =========================================================================

    /**
     * Create the type for a name field.
     *
     * @param NameField $context
     * @return bool|mixed
     */
    public static function getType(NameField $context): mixed
    {
        /** @var NameField $context */
        $typeName = $context->getGqlFieldContext()->handle . '_' . $context->handle . '_FormieNameInput';

        if ($inputType = GqlEntityRegistry::getEntity($typeName)) {
            return $inputType;
        }

        $fields = [];

        if ($context->useMultipleFields) {
            $subFields = ['prefix', 'firstName', 'middleName', 'lastName'];

            foreach ($subFields as $subField) {
                $required = $subField . 'Required';
                $enabled = $subField . 'Enabled';

                if ($context->{$enabled}) {
                    $fields[$subField] = [
                        'name' => $subField,
                        'type' => $context->{$required} ? Type::nonNull(Type::string()) : Type::string(),
                    ];
                }
            }
        } else {
            $fields['name'] = [
                'name' => 'name',
                'type' => $context->required ? Type::nonNull(Type::string()) : Type::string(),
            ];
        }

        return GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => function() use ($fields) {
                return $fields;
            },
            'normalizeValue' => [self::class, 'normalizeValue'],
        ]));
    }

    /**
     * Normalize GraphQL input data to what Formie expects.
     *
     * @param $value
     * @return mixed
     */
    public static function normalizeValue($value): mixed
    {
        if (!empty($value['name'])) {
            return $value['name'];
        }

        $nameModel = new NameModel();
        $nameModel->prefix = $value['prefix'] ?? null;
        $nameModel->firstName = $value['firstName'] ?? null;
        $nameModel->middleName = $value['middleName'] ?? null;
        $nameModel->lastName = $value['lastName'] ?? null;

        return $nameModel;
    }
}
