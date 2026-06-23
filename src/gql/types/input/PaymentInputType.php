<?php
namespace verbb\formie\gql\types\input;

use verbb\formie\base\Payment as PaymentIntegration;
use verbb\formie\fields\Payment as PaymentField;
use verbb\formie\Formie;

use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class PaymentInputType extends InputObjectType
{
    // Static Methods
    // =========================================================================

    public static function getType(PaymentField $context): mixed
    {
        $typeName = $context->getForm()->handle . '_' . $context->handle . '_FormiePaymentInput';

        if ($inputType = GqlEntityRegistry::getEntity($typeName)) {
            return $inputType;
        }

        return GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'description' => self::_getDescription($context),
            'fields' => fn() => self::_buildFields($context),
            'normalizeValue' => [self::class, 'normalizeValue'],
        ]));
    }

    public static function getTypeFromConfig(array $config): mixed
    {
        $fieldsService = Formie::$plugin->getFields();
        $typeName = $fieldsService->getFieldConfigGqlTypeName($config, 'PaymentInput');

        if ($inputType = GqlEntityRegistry::getEntity($typeName)) {
            return $inputType;
        }

        $field = $fieldsService->createField($config);

        if (!$field instanceof PaymentField) {
            return Type::string();
        }

        return self::getType($field);
    }

    public static function normalizeValue(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        return array_filter($value, static function($item) {
            return $item !== null && $item !== '';
        });
    }


    // Private Methods
    // =========================================================================

    private static function _buildFields(PaymentField $field): array
    {
        $fields = [];
        $integration = $field->getPaymentIntegration();

        if (!$integration instanceof PaymentIntegration) {
            return $fields;
        }

        foreach ($integration->getGraphqlPaymentInputFieldKeys($field) as $key) {
            $fields[$key] = [
                'name' => $key,
                'type' => Type::string(),
            ];
        }

        return $fields;
    }

    private static function _getDescription(PaymentField $field): string
    {
        $integration = $field->getPaymentIntegration();
        $provider = $integration?->getName() ?? 'payment provider';

        return "Provider references collected by your front-end and passed back to Formie for {$provider} processing.";
    }
}
