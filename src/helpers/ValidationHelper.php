<?php
namespace verbb\formie\helpers;

use verbb\formie\base\Field;
use verbb\formie\validators\FieldRequiredValidator;

use craft\base\Element;
use craft\base\ElementInterface;

use yii\validators\InlineValidator;
use yii\validators\RequiredValidator;
use yii\validators\Validator;
use yii\base\InvalidConfigException;

class ValidationHelper
{
    // Static Methods
    // =========================================================================

    public static function addPrefixedErrors(object $target, array $errors, string $prefix = ''): void
    {
        foreach ($errors as $attribute => $messages) {
            $attributeKey = (string)$attribute;
            $errorKey = $prefix !== '' ? "$prefix.$attributeKey" : $attributeKey;

            if (is_string($messages)) {
                if ($messages !== '') {
                    $target->addError($errorKey, $messages);
                }

                continue;
            }

            if (!is_array($messages)) {
                continue;
            }

            $isListOfStrings = array_is_list($messages) && array_reduce($messages, static fn(bool $carry, mixed $message): bool => $carry && is_string($message), true);

            if ($isListOfStrings) {
                foreach ($messages as $message) {
                    if ($message !== '') {
                        $target->addError($errorKey, $message);
                    }
                }

                continue;
            }

            self::addPrefixedErrors($target, $messages, $errorKey);
        }
    }

    public static function fieldValidationAttribute(Field $field): string
    {
        return 'field:' . $field->errorKey();
    }

    public static function validateField(ElementInterface $element, Field $field, mixed $value, ?string $attribute = null, ?string $requiredMessage = null): void
    {
        $attribute ??= self::fieldValidationAttribute($field);
        $scenario = $element->getScenario();
        $isEmpty = fn() => $field->isValueEmpty($value, $element);

        if ($scenario === Element::SCENARIO_LIVE && $field->required) {
            $config = ['isEmpty' => $isEmpty];

            if ($requiredMessage !== null) {
                $config['message'] = $requiredMessage;
                $requiredValidator = new FieldRequiredValidator($config);
            } else {
                $requiredValidator = new RequiredValidator($config);
            }

            $requiredValidator->validateAttribute($element, $attribute);
        }

        foreach ($field->getElementValidationRules() as $rule) {
            $validator = self::createFieldValidatorFromRule($element, $attribute, $rule, $field, $isEmpty);

            if (self::shouldValidateForScenario($scenario, $validator)) {
                $validator->validateAttributes($element);
            }
        }
    }

    public static function createFieldValidatorFromRule(ElementInterface $element, string $attribute, mixed $rule, Field $field, callable $isEmpty): Validator
    {
        if ($rule instanceof Validator) {
            return $rule;
        }

        $attributes = [$attribute];
        $validatorType = null;
        $options = [];

        if (is_string($rule)) {
            $validatorType = $rule;
        } else if (is_array($rule) && isset($rule[0])) {
            $validatorType = $rule[1] ?? $rule[0];
            $attributes = isset($rule[1]) ? (array)$rule[0] : $attributes;

            $attributes = array_map(static function(string $fieldAttribute) use ($field, $attribute) {
                return $fieldAttribute === $field->handle ? $attribute : $fieldAttribute;
            }, $attributes);

            $options = $rule;
            unset($options[0], $options[1]);
        } else {
            throw new InvalidConfigException('Invalid validation rule for custom field "' . $field->handle . '".');
        }

        if (
            (!is_string($validatorType) || !isset(Validator::$builtInValidators[$validatorType])) &&
            (is_callable($validatorType) || $field->hasMethod($validatorType))
        ) {
            $fieldValidator = $validatorType;
            $fieldParams = $options['params'] ?? null;

            $validatorType = function(string $attribute, mixed $params, InlineValidator $validator, mixed $current) use ($field, $fieldValidator): void {
                $method = $fieldValidator;

                if (is_string($method) && !is_callable($method)) {
                    $method = [$field, $method];
                }

                $method($this, $params);
            };

            $options['params'] = $fieldParams;
        }

        if (!array_key_exists('isEmpty', $options)) {
            $options['isEmpty'] = $isEmpty;
        }

        if (!array_key_exists('on', $options)) {
            $options['on'] = [$element::SCENARIO_DEFAULT, $element::SCENARIO_LIVE];
        }

        return Validator::createValidator($validatorType, $element, $attributes, $options);
    }

    public static function shouldValidateForScenario(string $scenario, Validator $validator): bool
    {
        return in_array($scenario, $validator->on) || (empty($validator->on) && !in_array($scenario, $validator->except));
    }
}
