<?php
namespace verbb\formie\gql\types\input;

use verbb\formie\fields\Name as NameField;
use verbb\formie\models\Name as NameModel;

use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class NameInputType extends InputObjectType
{
    // Static Methods
    // =========================================================================

    public static function getType(NameField $context): mixed
    {
        /** @var NameField $context */
        $typeName = $context->getForm()->handle . '_' . $context->handle . '_FormieNameInput';

        if ($inputType = GqlEntityRegistry::getEntity($typeName)) {
            return $inputType;
        }

        $fields = [];

        if ($context->useMultipleFields) {
            foreach ($context->getFields() as $subField) {
                if ($subField->enabled) {
                    $fields[$subField->handle] = [
                        'name' => $subField->handle,
                        'type' => $subField->required ? Type::nonNull(Type::string()) : Type::string(),
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
