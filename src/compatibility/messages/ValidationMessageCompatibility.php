<?php
namespace verbb\formie\compatibility\messages;

use verbb\formie\Formie;
use verbb\formie\helpers\ValidationMessagesHelper;

use Craft;

class ValidationMessageCompatibility
{
    /**
     * Legacy English source keys grouped by validation message key.
     *
     * Canonical copy lives in ValidationMessagesHelper::defaultTemplates(). These are
     * older keys kept for runtime lookup while compatibility mode is enabled.
     */
    public static function legacyKeysByMessageKey(): array
    {
        return [
            ValidationMessagesHelper::KEY_REQUIRED => [
                'This field is required.',
                '{attribute} cannot be blank.',
            ],
            ValidationMessagesHelper::KEY_UNIQUE => [
                '{attribute} must be unique.',
            ],
            ValidationMessagesHelper::KEY_MATCH => [
                '{attribute} must match {value}.',
            ],
            ValidationMessagesHelper::KEY_MIN_CHARACTERS => [
                'You must enter at least {limit} characters.',
                '{attribute} must be no less than {min} characters.',
            ],
            ValidationMessagesHelper::KEY_MAX_CHARACTERS => [
                '{attribute} must be no greater than {max} characters.',
            ],
            ValidationMessagesHelper::KEY_MIN_WORDS => [
                'You must enter at least {limit} words.',
                '{attribute} must be no less than {min} words.',
            ],
            ValidationMessagesHelper::KEY_MAX_WORDS => [
                '{attribute} must be no greater than {max} words.',
            ],
            ValidationMessagesHelper::KEY_EMAIL => [
                'Please enter a valid email address.',
                '{attribute} is not a valid email address.',
            ],
            ValidationMessagesHelper::KEY_URL => [
                'Please enter a valid URL.',
                '{attribute} is not a valid URL.',
            ],
            ValidationMessagesHelper::KEY_NUMBER => [
                '{label} is not a valid format.',
                'Please enter a valid number.',
                '{attribute} is not a valid number.',
                '{attribute} is not a valid format.',
            ],
            ValidationMessagesHelper::KEY_NUMBER_MIN => [
                'Please enter a value greater than or equal to {min}.',
                '{label} must be between {min} and {max}.',
                '{attribute} must be no less than {min}.',
                '{attribute} must be between {min} and {max}.',
            ],
            ValidationMessagesHelper::KEY_NUMBER_MAX => [
                'Please enter a value less than or equal to {max}.',
                '{label} must be between {min} and {max}.',
                '{attribute} must be no greater than {max}.',
                '{attribute} must be between {min} and {max}.',
            ],
            ValidationMessagesHelper::KEY_MIN_OPTIONS => [
                '{label} must select no less than {min}.',
                '{attribute} must select no less than {min}.',
            ],
            ValidationMessagesHelper::KEY_MAX_OPTIONS => [
                '{label} must select no greater than {max}.',
                '{attribute} must select no greater than {max}.',
            ],
            ValidationMessagesHelper::KEY_MAX_FILE_SIZE => [
                'File {filename} must be smaller than {filesize} MB.',
            ],
            ValidationMessagesHelper::KEY_INVALID => [
                '{label} has an invalid value.',
                '{attribute} has an invalid value.',
            ],
        ];
    }

    public static function legacyKeysForMessageKey(string $messageKey): array
    {
        return self::legacyKeysByMessageKey()[$messageKey] ?? [];
    }

    /**
     * Legacy English source keys for a canonical validation template string.
     *
     * @return string[]
     */
    public static function legacyKeysFor(string $canonical): array
    {
        $legacyKeys = [];

        foreach (ValidationMessagesHelper::defaultTemplates() as $messageKey => $template) {
            if ($template !== $canonical) {
                continue;
            }

            $legacyKeys = self::legacyKeysForMessageKey($messageKey);

            break;
        }

        return $legacyKeys;
    }

    /**
     * @return string[]
     */
    public static function allLegacyTranslationKeys(): array
    {
        $keys = [];

        foreach (self::legacyKeysByMessageKey() as $legacyKeys) {
            foreach ($legacyKeys as $legacyKey) {
                $keys[] = $legacyKey;
            }
        }

        return array_values(array_unique($keys));
    }

    public static function isEnabled(): bool
    {
        return Formie::$plugin?->getCompatibility()->isCompatibilityModeEnabled() ?? true;
    }

    /**
     * Translate a canonical validation string, falling back to legacy keys when needed.
     */
    public static function translate(string $message, array $params = []): string
    {
        $params = self::_normalizeTranslationParams($params);
        $translated = Craft::t('formie', $message, $params);

        if (!self::isEnabled()) {
            return $translated;
        }

        if ($translated !== $message) {
            return $translated;
        }

        foreach (self::legacyKeysFor($message) as $legacyKey) {
            $legacyTranslated = Craft::t('formie', $legacyKey, $params);

            if ($legacyTranslated !== $legacyKey) {
                return $legacyTranslated;
            }
        }

        return $translated;
    }

    /**
     * Include legacy source keys in front-end translation seed lists.
     *
     * @return string[]
     */
    public static function expandTranslationStrings(array $strings): array
    {
        if (!self::isEnabled()) {
            return $strings;
        }

        $expanded = $strings;

        foreach ($strings as $string) {
            foreach (self::legacyKeysFor($string) as $legacyKey) {
                $expanded[] = $legacyKey;
            }
        }

        foreach (self::allLegacyTranslationKeys() as $legacyKey) {
            $expanded[] = $legacyKey;
        }

        return array_values(array_unique($expanded));
    }

    /**
     * Copy canonical translated values onto legacy keys for older JS lookups.
     */
    public static function applyTranslationAliases(array $translations): array
    {
        if (!self::isEnabled()) {
            return $translations;
        }

        foreach (ValidationMessagesHelper::defaultTemplates() as $messageKey => $canonical) {
            if (!array_key_exists($canonical, $translations)) {
                continue;
            }

            $canonicalValue = $translations[$canonical];

            foreach (self::legacyKeysForMessageKey($messageKey) as $legacyKey) {
                if (!array_key_exists($legacyKey, $translations) || $translations[$legacyKey] === $legacyKey) {
                    $translations[$legacyKey] = $canonicalValue;
                }
            }
        }

        return $translations;
    }

    /**
     * Propagate plugin validation defaults onto legacy front-end translation keys.
     */
    public static function applyPluginDefaultAliases(array $translations, string $messageKey, string $pluginDefault): array
    {
        if (!self::isEnabled()) {
            return $translations;
        }

        foreach (self::legacyKeysForMessageKey($messageKey) as $legacyKey) {
            $translations[$legacyKey] = $pluginDefault;
        }

        return $translations;
    }

    private static function _normalizeTranslationParams(array $params): array
    {
        if (!array_key_exists('attribute', $params) && array_key_exists('label', $params)) {
            $params['attribute'] = $params['label'];
        }

        if (!array_key_exists('name', $params) && array_key_exists('label', $params)) {
            $params['name'] = $params['label'];
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
