<?php
namespace verbb\formie\helpers;

use verbb\formie\base\Field;
use verbb\formie\compatibility\messages\ValidationMessageCompatibility;
use verbb\formie\Formie;

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
            self::KEY_REQUIRED => '{label} cannot be blank.',
            self::KEY_UNIQUE => '“{label}” must be unique.',
            self::KEY_MATCH => '{label} must match {value}.',
            self::KEY_MIN_CHARACTERS => '{label} must be no less than {min} characters.',
            self::KEY_MAX_CHARACTERS => '{label} must be no greater than {max} characters.',
            self::KEY_MIN_WORDS => '{label} must be no less than {min} words.',
            self::KEY_MAX_WORDS => '{label} must be no greater than {max} words.',
            self::KEY_EMAIL => '{label} is not a valid email address.',
            self::KEY_URL => '{label} is not a valid URL.',
            self::KEY_NUMBER => '{label} is not a valid number.',
            self::KEY_NUMBER_MIN => '{label} must be no less than {min}.',
            self::KEY_NUMBER_MAX => '{label} must be no greater than {max}.',
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

        $pluginDefault = self::pluginDefault($key);

        if ($pluginDefault !== null) {
            return self::interpolate($pluginDefault, $params);
        }

        $template = self::defaultTemplate($key);

        if ($template === null) {
            return self::translateDefaultTemplate(self::KEY_INVALID, $params);
        }

        return self::translateDefaultTemplate($key, $params);
    }

    public static function pluginDefault(string $key): ?string
    {
        if (!Formie::$plugin) {
            return null;
        }

        $defaults = Formie::$plugin->getSettings()->validationMessageDefaults ?? [];
        $message = trim((string)($defaults[$key] ?? ''));

        return $message !== '' ? $message : null;
    }

    public static function normalizeDefaultsForStorage(array $defaults): array
    {
        $normalized = [];
        $allowedKeys = array_keys(self::defaultTemplates());

        foreach ($defaults as $key => $message) {
            if (!in_array($key, $allowedKeys, true) || !is_string($message)) {
                continue;
            }

            $message = trim(self::normalizeDefaultTokens($message));

            if ($message === '') {
                continue;
            }

            $predefined = self::defaultTemplate($key);

            if ($predefined !== null && $message === $predefined) {
                continue;
            }

            $normalized[$key] = $message;
        }

        return $normalized;
    }

    public static function normalizeDefaultTokens(string $message): string
    {
        return str_replace(['{name}', '{attribute}'], '{label}', $message);
    }

    public static function defaultsSchemaNodes(): array
    {
        return array_merge(
            self::_defaultsSchemaSection(Craft::t('formie', 'General'), [
                [self::KEY_REQUIRED, ['label']],
                [self::KEY_UNIQUE, ['label']],
                [self::KEY_MATCH, ['label', 'value']],
                [self::KEY_INVALID, ['label']],
            ]),
            self::_defaultsSchemaSection(Craft::t('formie', 'Text Limits'), [
                [self::KEY_MIN_CHARACTERS, ['label', 'limit', 'min']],
                [self::KEY_MAX_CHARACTERS, ['label', 'limit', 'max']],
                [self::KEY_MIN_WORDS, ['label', 'limit', 'min']],
                [self::KEY_MAX_WORDS, ['label', 'limit', 'max']],
            ]),
            self::_defaultsSchemaSection(Craft::t('formie', 'Format Validation'), [
                [self::KEY_EMAIL, ['label']],
                [self::KEY_URL, ['label']],
                [self::KEY_NUMBER, ['label']],
                [self::KEY_NUMBER_MIN, ['label', 'min']],
                [self::KEY_NUMBER_MAX, ['label', 'max']],
                [self::KEY_BLOCKED_DOMAIN, ['domain']],
            ]),
            self::_defaultsSchemaSection(Craft::t('formie', 'Options'), [
                [self::KEY_MIN_OPTIONS, ['label', 'min']],
                [self::KEY_MAX_OPTIONS, ['label', 'max']],
            ]),
            self::_defaultsSchemaSection(Craft::t('formie', 'File Upload'), [
                [self::KEY_MAX_FILES, ['files']],
                [self::KEY_MIN_FILE_SIZE, ['filesize']],
                [self::KEY_MAX_FILE_SIZE, ['filesize']],
            ]),
        );
    }

    public static function applyPluginDefaultsToFrontendTranslations(array $translations): array
    {
        foreach (self::defaultTemplates() as $key => $predefinedTemplate) {
            $pluginDefault = self::pluginDefault($key);

            if ($pluginDefault === null) {
                continue;
            }

            if ($predefinedTemplate !== null) {
                $translations[$predefinedTemplate] = $pluginDefault;
            }

            $translations = ValidationMessageCompatibility::applyPluginDefaultAliases($translations, $key, $pluginDefault);
        }

        return $translations;
    }

    public static function frontendTranslationStringList(): array
    {
        $strings = array_merge(
            array_values(self::defaultTemplates()),
            self::frontendGeneralTranslationStrings(),
        );

        return ValidationMessageCompatibility::expandTranslationStrings(
            array_values(array_unique($strings)),
        );
    }

    /**
     * Canonical English keys for static translation file regen (no legacy compat aliases).
     */
    public static function translationExtractStringList(): array
    {
        return array_values(array_unique(array_merge(
            array_values(self::defaultTemplates()),
            self::frontendGeneralTranslationStrings(),
        )));
    }

    /**
     * Front-end-only strings seeded into the JS translation payload.
     *
     * Plain English literals — not Craft::t() — because Rendering::getFrontendJsTranslations()
     * passes each string through Craft::t('formie', $message) at runtime. The literal is both the
     * message key and the default value in formie.php. Keep in sync with @verbb/formie-browser fallbacks.
     */
    public static function frontendGeneralTranslationStrings(): array
    {
        return [
            '{count, plural, one{character allowed} other{characters allowed}}',
            '{count, plural, one{character left} other{characters left}}',
            '{count, plural, one{character over limit} other{characters over limit}}',
            '{count, plural, one{word allowed} other{words allowed}}',
            '{count, plural, one{word left} other{words left}}',
            '{count, plural, one{word over limit} other{words over limit}}',
            'Unable to parse response `{e}`.',
            'Are you sure you want to leave?',
            'The request timed out.',
            'The request encountered a network error. Please try again.',
            'Invalid number',
            'Invalid country code',
            'Too short',
            'Too long',
            'Missing Authorization ID for approval.',
            'Payment authorized. Finalize the form to complete payment.',
            'Unable to authorize payment. Please try again.',
            'Invalid amount.',
            'Invalid currency.',
            'Provide a value for “{label}” to proceed.',
            'Captcha challenge must be completed.',
        ];
    }

    public static function frontendTranslationSourceStrings(string $key): array
    {
        return ValidationMessageCompatibility::legacyKeysForMessageKey($key);
    }

    public static function override(Field $field, string $key): ?string
    {
        $field = self::messageField($field);
        $messages = $field->validationMessages ?? [];
        $message = trim((string)($messages[$key] ?? ''));

        if ($message === '' && $key === self::KEY_REQUIRED) {
            $message = trim((string)($field->errorMessage ?? ''));
        }

        return $message !== '' ? $message : null;
    }

    public static function messageField(Field $field): Field
    {
        $parent = $field->getParentField();

        return $parent instanceof Field ? $parent : $field;
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

    public static function fileUploadValidationClientAttributes(
        Field $field,
        int $limitFiles,
        float $sizeMinLimit,
        float $sizeMaxLimit,
    ): array {
        $attrs = self::requiredClientAttributes($field);

        if ($limitFiles) {
            $attrs[self::clientAttribute(self::KEY_MAX_FILES)] = $field->getValidationMessageClientAttribute(self::KEY_MAX_FILES, [
                'files' => $limitFiles,
            ]);
        }

        if ($sizeMinLimit) {
            $attrs[self::clientAttribute(self::KEY_MIN_FILE_SIZE)] = $field->getValidationMessageClientAttribute(self::KEY_MIN_FILE_SIZE, [
                'filesize' => $sizeMinLimit,
            ]);
        }

        if ($sizeMaxLimit) {
            $attrs[self::clientAttribute(self::KEY_MAX_FILE_SIZE)] = $field->getValidationMessageClientAttribute(self::KEY_MAX_FILE_SIZE, [
                'filesize' => $sizeMaxLimit,
            ]);
        }

        return $attrs;
    }

    public static function optionsLimitClientAttributes(Field $field, bool $limitOptions, mixed $min, mixed $max): array
    {
        $attrs = [];

        if (!$limitOptions) {
            return $attrs;
        }

        if ($min) {
            $attrs[self::clientAttribute(self::KEY_MIN_OPTIONS)] = $field->getValidationMessageClientAttribute(self::KEY_MIN_OPTIONS, [
                'min' => $min,
            ]);
        }

        if ($max) {
            $attrs[self::clientAttribute(self::KEY_MAX_OPTIONS)] = $field->getValidationMessageClientAttribute(self::KEY_MAX_OPTIONS, [
                'max' => $max,
            ]);
        }

        return $attrs;
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
        return match ($key) {
            self::KEY_REQUIRED => Craft::t('formie', 'Required Error Message'),
            self::KEY_UNIQUE => Craft::t('formie', 'Unique Error Message'),
            self::KEY_MATCH => Craft::t('formie', 'Match Field Error Message'),
            self::KEY_MIN_CHARACTERS => Craft::t('formie', 'Minimum Characters Error Message'),
            self::KEY_MAX_CHARACTERS => Craft::t('formie', 'Maximum Characters Error Message'),
            self::KEY_MIN_WORDS => Craft::t('formie', 'Minimum Words Error Message'),
            self::KEY_MAX_WORDS => Craft::t('formie', 'Maximum Words Error Message'),
            self::KEY_EMAIL => Craft::t('formie', 'Invalid Email Error Message'),
            self::KEY_URL => Craft::t('formie', 'Invalid URL Error Message'),
            self::KEY_NUMBER => Craft::t('formie', 'Invalid Number Error Message'),
            self::KEY_NUMBER_MIN => Craft::t('formie', 'Minimum Value Error Message'),
            self::KEY_NUMBER_MAX => Craft::t('formie', 'Maximum Value Error Message'),
            self::KEY_BLOCKED_DOMAIN => Craft::t('formie', 'Blocked Domain Error Message'),
            self::KEY_MIN_OPTIONS => Craft::t('formie', 'Minimum Options Error Message'),
            self::KEY_MAX_OPTIONS => Craft::t('formie', 'Maximum Options Error Message'),
            self::KEY_MAX_FILES => Craft::t('formie', 'Maximum Files Error Message'),
            self::KEY_MIN_FILE_SIZE => Craft::t('formie', 'Minimum File Size Error Message'),
            self::KEY_MAX_FILE_SIZE => Craft::t('formie', 'Maximum File Size Error Message'),
            self::KEY_INVALID => Craft::t('formie', 'Invalid Error Message'),
            default => Craft::t('formie', 'Error Message'),
        };
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

    private static function translateDefaultTemplate(string $key, array $params): string
    {
        $message = self::defaultTemplate($key) ?? self::defaultTemplate(self::KEY_INVALID) ?? '{label} is invalid.';

        return ValidationMessageCompatibility::translate($message, $params);
    }

    private static function _defaultsSchemaSection(string $heading, array $fields): array
    {
        $nodes = [
            [
                '$el' => 'h3',
                'children' => $heading,
                'attrs' => [
                    'class' => 'formie-defaults-subsection-title',
                ],
            ],
        ];

        foreach ($fields as [$key, $tokens]) {
            $nodes[] = self::_defaultsSchemaField($key, $tokens);
        }

        return $nodes;
    }

    private static function _defaultsSchemaField(string $key, array $tokens): array
    {
        $defaultTemplate = self::defaultTemplate($key);

        return SchemaHelper::validationMessageField([
            'messageKey' => $key,
            'name' => 'validationMessageDefaults.' . $key,
            'tokens' => $tokens,
            'instructions' => $defaultTemplate
                ? Craft::t('formie', 'Placeholders: {tokens}. Built-in default: “{template}”.', [
                    'tokens' => implode(', ', array_map(static fn(string $token): string => '`{' . $token . '}`', $tokens)),
                    'template' => $defaultTemplate,
                ])
                : self::tokenInstructions($tokens),
        ]);
    }

    private static function normalizeParams(Field $field, array $params): array
    {
        $label = (string)$field->label;

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
