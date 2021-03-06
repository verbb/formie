<?php
namespace verbb\formie\gql\types\input;

use verbb\formie\fields\formfields\Address as AddressField;
use verbb\formie\models\Address as AddressModel;

use Craft;
use craft\base\Field;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\QueryArgument;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class AddressInputType extends InputObjectType
{
    /**
     * Create the type for a address field.
     *
     * @param $context
     * @return bool|mixed
     */
    public static function getType(AddressField $context)
    {
        /** @var AddressField $context */
        $typeName = $context->getFieldContext()->handle . '_' . $context->handle . '_FormieAddressInput';

        if ($inputType = GqlEntityRegistry::getEntity($typeName)) {
            return $inputType;
        }

        $fields = [];

        $subFields = ['address1', 'address2', 'address3', 'city', 'state', 'zip', 'country'];

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

        $inputType = GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => function() use ($fields) {
                return $fields;
            },
            'normalizeValue' => [self::class, 'normalizeValue'],
        ]));

        return $inputType;
    }

    /**
     * Normalize GraphQL input data to what Formie expects.
     *
     * @param $value
     * @return mixed
     */
    public static function normalizeValue($value)
    {
        if (!empty($value['name'])) {
            return $value['name'];
        }

        $addressModel = new AddressModel();
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
