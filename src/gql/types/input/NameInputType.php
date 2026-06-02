<?php
namespace verbb\formie\gql\types\input;

use verbb\formie\Formie;
use verbb\formie\fields\Name as NameField;
use verbb\formie\fields\values\NameFieldValue;

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

    public static function getTypeFromConfig(array $config): mixed
    {
        $fieldsService = Formie::$plugin->getFields();
        $typeName = $fieldsService->getFieldConfigGqlTypeName($config, 'NameInput');

        if ($inputType = GqlEntityRegistry::getEntity($typeName)) {
            return $inputType;
        }

        $fields = [];
        $settings = $fieldsService->getFieldConfigSettings($config);

        if (!empty($settings['useMultipleFields'])) {
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
        } else {
            $fields['name'] = [
                'name' => 'name',
                'type' => !empty($config['required']) ? Type::nonNull(Type::string()) : Type::string(),
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

        $nameValue = new NameFieldValue();
        $nameValue->prefix = $value['prefix'] ?? null;
        $nameValue->firstName = $value['firstName'] ?? null;
        $nameValue->middleName = $value['middleName'] ?? null;
        $nameValue->lastName = $value['lastName'] ?? null;

        return $nameValue;
    }
}
