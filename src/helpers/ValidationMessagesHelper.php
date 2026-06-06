<?php
namespace verbb\formie\helpers;

use verbb\formie\base\Field;

use Craft;
use craft\helpers\StringHelper;

class ValidationMessagesHelper
{
    // Constants
    // =========================================================================

    public const KEY_REQUIRED = 'required';
    public const KEY_UNIQUE = 'unique';
    public const KEY_MATCH = 'match';
    public const KEY_MIN_CHARACTERS = 'minCharacters';
    public const KEY_MAX_CHARACTERS = 'maxCharacters';
    public const KEY_MIN_WORDS = 'minWords';
    public const KEY_MAX_WORDS = 'maxWords';
    public const KEY_EMAIL = 'email';
    public const KEY_URL = 'url';
    public const KEY_NUMBER = 'number';
    public const KEY_NUMBER_MIN = 'numberMin';
    public const KEY_NUMBER_MAX = 'numberMax';
    public const KEY_BLOCKED_DOMAIN = 'blockedDomain';
    public const KEY_MIN_OPTIONS = 'minOptions';
    public const KEY_MAX_OPTIONS = 'maxOptions';
    public const KEY_MAX_FILES = 'maxFiles';
    public const KEY_MIN_FILE_SIZE = 'minFileSize';
    public const KEY_MAX_FILE_SIZE = 'maxFileSize';
    public const KEY_INVALID = 'invalid';


    // Static Methods
    // =========================================================================

    public static function allowedTokens(): array
    {
        return ['label', 'value', 'min', 'max', 'limit', 'domain', 'files', 'filesize', 'filename'];
    }

    public static function defaultTemplate(string $key): ?string
    {
        return self::defaultTemplates()[$key] ?? null;
    }

    public static function defaultTemplates(): array
    {
        return [
            self::KEY_REQUIRED => 'This field is required.',
            self::KEY_UNIQUE => '“{label}” must be unique.',
            self::KEY_MATCH => '{label} must match {value}.',
            self::KEY_MIN_CHARACTERS => 'You must enter at least {limit} characters.',
            self::KEY_MAX_CHARACTERS => '{label} must be no greater than {max} characters.',
            self::KEY_MIN_WORDS => 'You must enter at least {limit} words.',
            self::KEY_MAX_WORDS => '{label} must be no greater than {max} words.',
            self::KEY_EMAIL => 'Please enter a valid email address.',
            self::KEY_URL => 'Please enter a valid URL.',
            self::KEY_NUMBER => 'Please enter a valid number.',
            self::KEY_NUMBER_MIN => 'Please enter a value greater than or equal to {min}.',
            self::KEY_NUMBER_MAX => 'Please enter a value less than or equal to {max}.',
            self::KEY_BLOCKED_DOMAIN => '“{domain}” is not allowed.',
            self::KEY_MIN_OPTIONS => '{label} should contain at least {min, number} {min, plural, one{option} other{options}}.',
            self::KEY_MAX_OPTIONS => '{label} should contain at most {max, number} {max, plural, one{option} other{options}}.',
            self::KEY_MAX_FILES => 'Choose up to {files} files.',
            self::KEY_MIN_FILE_SIZE => 'File must be larger than {filesize} MB.',
            self::KEY_MAX_FILE_SIZE => 'File must be smaller than {filesize} MB.',
            self::KEY_INVALID => '{label} is invalid.',
        ];
    }

    public static function resolve(Field $field, string $key, array $params = []): string
    {
        $params = self::normalizeParams($field, $params);
        $override = self::override($field, $key);

        if ($override !== null) {
            return self::interpolate($override, $params);
        }

        $template = self::defaultTemplate($key);

        if ($template === null) {
            return Craft::t('formie', self::defaultTemplate(self::KEY_INVALID) ?? '{label} is invalid.', $params);
        }

        return Craft::t('formie', $template, $params);
    }

    public static function override(Field $field, string $key): ?string
    {
        $messages = $field->validationMessages ?? [];
        $message = trim((string)($messages[$key] ?? ''));

        if ($message === '' && $key === self::KEY_REQUIRED) {
            $message = trim((string)($field->errorMessage ?? ''));
        }

        return $message !== '' ? $message : null;
    }

    public static function requiredClientAttributes(Field $field): array
    {
        return [
            'data-formie-required-message' => $field->getValidationMessageClientAttribute(self::KEY_REQUIRED),
        ];
    }

    public static function clientAttribute(string $key): string
    {
        return 'data-formie-validation-' . StringHelper::toKebabCase($key) . '-message';
    }

    public static function textLimitClientAttributes(
        Field $field,
        bool $limit,
        mixed $min,
        mixed $max,
        ?string $minType,
        ?string $maxType,
    ): array {
        $attrs = [
            'data-formie-required-message' => $field->getValidationMessageClientAttribute(self::KEY_REQUIRED),
        ];

        if (!$limit) {
            return $attrs;
        }

        if ($minType === 'characters' && $min) {
            $attrs['data-formie-min-chars'] = $min;
            $attrs[self::clientAttribute(self::KEY_MIN_CHARACTERS)] = $field->getValidationMessageClientAttribute(self::KEY_MIN_CHARACTERS, [
                'limit' => $min,
                'min' => $min,
            ]);
        }

        if ($maxType === 'characters' && $max) {
            $attrs['data-formie-max-chars'] = $max;
            $attrs[self::clientAttribute(self::KEY_MAX_CHARACTERS)] = $field->getValidationMessageClientAttribute(self::KEY_MAX_CHARACTERS, [
                'limit' => $max,
                'max' => $max,
            ]);
        }

        if ($minType === 'words' && $min) {
            $attrs['data-formie-min-words'] = $min;
            $attrs[self::clientAttribute(self::KEY_MIN_WORDS)] = $field->getValidationMessageClientAttribute(self::KEY_MIN_WORDS, [
                'limit' => $min,
                'min' => $min,
            ]);
        }

        if ($maxType === 'words' && $max) {
            $attrs['data-formie-max-words'] = $max;
            $attrs[self::clientAttribute(self::KEY_MAX_WORDS)] = $field->getValidationMessageClientAttribute(self::KEY_MAX_WORDS, [
                'limit' => $max,
                'max' => $max,
            ]);
        }

        return $attrs;
    }

    public static function numberValidationClientAttributes(Field $field, bool $limit, mixed $min, mixed $max): array
    {
        $attrs = [
            'data-formie-required-message' => $field->getValidationMessageClientAttribute(self::KEY_REQUIRED),
            self::clientAttribute(self::KEY_NUMBER) => $field->getValidationMessageClientAttribute(self::KEY_NUMBER),
        ];

        if ($limit && $min) {
            $attrs[self::clientAttribute(self::KEY_NUMBER_MIN)] = $field->getValidationMessageClientAttribute(self::KEY_NUMBER_MIN, [
                'min' => $min,
            ]);
        }

        if ($limit && $max) {
            $attrs[self::clientAttribute(self::KEY_NUMBER_MAX)] = $field->getValidationMessageClientAttribute(self::KEY_NUMBER_MAX, [
                'max' => $max,
            ]);
        }

        return $attrs;
    }

    public static function emailValidationClientAttributes(Field $field): array
    {
        return [
            'data-formie-required-message' => $field->getValidationMessageClientAttribute(self::KEY_REQUIRED),
            self::clientAttribute(self::KEY_EMAIL) => $field->getValidationMessageClientAttribute(self::KEY_EMAIL),
        ];
    }

    public static function interpolate(string $template, array $params): string
    {
        $search = [];
        $replace = [];

        foreach ($params as $name => $value) {
            if (!is_scalar($value) && $value !== null) {
                continue;
            }

            $search[] = '{' . $name . '}';
            $replace[] = (string)$value;
        }

        return str_replace($search, $replace, $template);
    }

    public static function tokenInstructions(array $tokens = []): string
    {
        $tokens = $tokens ?: self::allowedTokens();

        return Craft::t('formie', 'Leave empty to use the default message. Allowed placeholders: {tokens}.', [
            'tokens' => implode(', ', array_map(static fn(string $token): string => '`{' . $token . '}`', $tokens)),
        ]);
    }

    public static function builderLabel(string $key): string
    {
        return Craft::t('formie', self::builderLabels()[$key] ?? 'Error Message');
    }

    public static function builderLabels(): array
    {
        return [
            self::KEY_REQUIRED => 'Required Error Message',
            self::KEY_UNIQUE => 'Unique Error Message',
            self::KEY_MATCH => 'Match Field Error Message',
            self::KEY_MIN_CHARACTERS => 'Minimum Characters Error Message',
            self::KEY_MAX_CHARACTERS => 'Maximum Characters Error Message',
            self::KEY_MIN_WORDS => 'Minimum Words Error Message',
            self::KEY_MAX_WORDS => 'Maximum Words Error Message',
            self::KEY_EMAIL => 'Invalid Email Error Message',
            self::KEY_URL => 'Invalid URL Error Message',
            self::KEY_NUMBER => 'Invalid Number Error Message',
            self::KEY_NUMBER_MIN => 'Minimum Value Error Message',
            self::KEY_NUMBER_MAX => 'Maximum Value Error Message',
            self::KEY_BLOCKED_DOMAIN => 'Blocked Domain Error Message',
            self::KEY_MIN_OPTIONS => 'Minimum Options Error Message',
            self::KEY_MAX_OPTIONS => 'Maximum Options Error Message',
            self::KEY_MAX_FILES => 'Maximum Files Error Message',
            self::KEY_MIN_FILE_SIZE => 'Minimum File Size Error Message',
            self::KEY_MAX_FILE_SIZE => 'Maximum File Size Error Message',
            self::KEY_INVALID => 'Invalid Error Message',
        ];
    }

    private static function normalizeParams(Field $field, array $params): array
    {
        $label = Craft::t('formie', $field->label);

        if (!array_key_exists('label', $params)) {
            $params['label'] = $label;
        }

        // Legacy placeholder aliases for migrated overrides — use {label} in new overrides.
        if (!array_key_exists('name', $params)) {
            $params['name'] = $params['label'];
        }

        if (!array_key_exists('attribute', $params)) {
            $params['attribute'] = $params['label'];
        }

        if (!array_key_exists('limit', $params) && array_key_exists('min', $params)) {
            $params['limit'] = $params['min'];
        }

        if (!array_key_exists('limit', $params) && array_key_exists('max', $params)) {
            $params['limit'] = $params['max'];
        }

        return $params;
    }
}
