<?php
namespace verbb\formie\helpers;

use verbb\formie\base\Field;
use verbb\formie\validators\FieldRequiredValidator;

use craft\base\Element;
use craft\base\ElementInterface;
use craft\validators\UrlValidator as CraftUrlValidator;

use yii\validators\EmailValidator;
use yii\validators\InlineValidator;
use yii\validators\NumberValidator;
use yii\validators\RequiredValidator;
use yii\validators\UrlValidator as YiiUrlValidator;
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
                $config['message'] = ValidationMessagesHelper::resolve($field, ValidationMessagesHelper::KEY_REQUIRED);
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

        if (is_string($validatorType)) {
            $options = self::applyFormieValidatorMessages($validatorType, $field, $options);
        }

        return Validator::createValidator($validatorType, $element, $attributes, $options);
    }

    public static function shouldValidateForScenario(string $scenario, Validator $validator): bool
    {
        return in_array($scenario, $validator->on) || (empty($validator->on) && !in_array($scenario, $validator->except));
    }

    /**
     * Replace Yii validator defaults with Formie-owned, translated messages using {label}.
     *
     * Yii uses {attribute} against internal keys like field:handle — not field labels.
     */
    private static function applyFormieValidatorMessages(string $validatorType, Field $field, array $options): array
    {
        $messageKey = self::validatorMessageKey($validatorType);

        if ($messageKey !== null && !array_key_exists('message', $options)) {
            $options['message'] = ValidationMessagesHelper::resolve($field, $messageKey);
        }

        if (self::isNumberValidator($validatorType)) {
            $min = $options['min'] ?? null;
            $max = $options['max'] ?? null;

            if ($min !== null && !array_key_exists('tooSmall', $options)) {
                $options['tooSmall'] = ValidationMessagesHelper::resolve($field, ValidationMessagesHelper::KEY_NUMBER_MIN, [
                    'min' => $min,
                ]);
            }

            if ($max !== null && !array_key_exists('tooBig', $options)) {
                $options['tooBig'] = ValidationMessagesHelper::resolve($field, ValidationMessagesHelper::KEY_NUMBER_MAX, [
                    'max' => $max,
                ]);
            }
        }

        return $options;
    }

    private static function validatorMessageKey(string $validatorType): ?string
    {
        return match ($validatorType) {
            'email', EmailValidator::class => ValidationMessagesHelper::KEY_EMAIL,
            'url', CraftUrlValidator::class, YiiUrlValidator::class => ValidationMessagesHelper::KEY_URL,
            'number', NumberValidator::class => ValidationMessagesHelper::KEY_NUMBER,
            default => null,
        };
    }

    private static function isNumberValidator(string $validatorType): bool
    {
        return in_array($validatorType, ['number', NumberValidator::class], true);
    }
}
