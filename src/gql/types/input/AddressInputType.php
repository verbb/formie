<?php
namespace verbb\formie\gql\types\input;

use verbb\formie\fields\Address as AddressField;
use verbb\formie\models\Address as AddressModel;

use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class AddressInputType extends InputObjectType
{
    // Static Methods
    // =========================================================================

    public static function getType(AddressField $context): mixed
    {
        /** @var AddressField $context */
        $typeName = $context->getForm()->handle . '_' . $context->handle . '_FormieAddressInput';

        if ($inputType = GqlEntityRegistry::getEntity($typeName)) {
            return $inputType;
        }

        $fields = [];

        foreach ($context->getFields() as $subField) {
            if ($subField->enabled) {
                $fields[$subField->handle] = [
                    'name' => $subField->handle,
                    'type' => $subField->required ? Type::nonNull(Type::string()) : Type::string(),
                ];
            }
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

        $addressModel = new AddressModel();
        $addressModel->autoComplete = $value['autoComplete'] ?? null;
        $addressModel->address1 = $value['address1'] ?? null;
        $addressModel->address2 = $value['address2'] ?? null;
        $addressModel->address3 = $value['address3'] ?? null;
        $addressModel->city = $value['city'] ?? null;
        $addressModel->state = $value['state'] ?? null;
        $addressModel->zip = $value['zip'] ?? null;
        $addressModel->country = $value['country'] ?? null;

        return $addressModel;
    }
}
