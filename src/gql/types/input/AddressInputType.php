<?php
namespace verbb\formie\gql\types\input;

use verbb\formie\Formie;
use verbb\formie\fields\Address as AddressField;
use verbb\formie\fields\values\AddressFieldValue;

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

    public static function getTypeFromConfig(array $config): mixed
    {
        $fieldsService = Formie::$plugin->getFields();
        $typeName = $fieldsService->getFieldConfigGqlTypeName($config, 'AddressInput');

        if ($inputType = GqlEntityRegistry::getEntity($typeName)) {
            return $inputType;
        }

        $fields = [];

        foreach ($fieldsService->getNestedFieldConfigs($config) as $subFieldConfig) {
            if (($subFieldConfig['enabled'] ?? true) === false) {
                continue;
            }

            $handle = $subFieldConfig['handle'] ?? null;

            if (!$handle) {
                continue;
            }

            $fields[$handle] = [
                'name' => $handle,
                'type' => !empty($subFieldConfig['required']) ? Type::nonNull(Type::string()) : Type::string(),
            ];
        }

        return GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => fn() => $fields,
            'normalizeValue' => [self::class, 'normalizeValue'],
        ]));
    }

    public static function normalizeValue($value): mixed
    {
        if (!empty($value['name'])) {
            return $value['name'];
        }

        $addressValue = new AddressFieldValue();
        $addressValue->autoComplete = $value['autoComplete'] ?? null;
        $addressValue->address1 = $value['address1'] ?? null;
        $addressValue->address2 = $value['address2'] ?? null;
        $addressValue->address3 = $value['address3'] ?? null;
        $addressValue->city = $value['city'] ?? null;
        $addressValue->state = $value['state'] ?? null;
        $addressValue->zip = $value['zip'] ?? null;
        $addressValue->country = $value['country'] ?? null;

        return $addressValue;
    }
}
