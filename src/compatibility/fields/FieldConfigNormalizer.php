<?php
namespace verbb\formie\compatibility\fields;

use verbb\formie\fields;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\FieldAttributesHelper;
use verbb\formie\helpers\OptionsMode;
use verbb\formie\options\OptionSourceConfigHelper;
use verbb\formie\positions\AboveInput;
use verbb\formie\positions\BelowInput;

use craft\helpers\Json;

class FieldConfigNormalizer
{
    // Static Methods
    // =========================================================================

    public static function normalize(array &$config, string $fieldClass): void
    {
        unset($config['columnWidth']);

        self::_normalizeLegacyTextLimitConfig($config, $fieldClass);
        self::_removeUnsupportedLimitConfig($config, $fieldClass);
        self::_normalizeLegacyPositions($config);
        self::_normalizeLegacyFieldConfig($config);
        self::_normalizeValidationMessages($config, $fieldClass);
        self::_normalizeLegacyComboboxConfig($config, $fieldClass);
        self::_normalizeOptionsFieldConfig($config, $fieldClass);
        self::_normalizeRecipientsFieldConfig($config, $fieldClass);
        self::_normalizeFieldAttributes($config);
        self::_removeLegacyProperties($config);
    }

    private static function _normalizeLegacyTextLimitConfig(array &$config, string $fieldClass): void
    {
        if (!in_array($fieldClass, self::_supportedLimitConfigTypes(), true)) {
            unset($config['limitType'], $config['limitAmount']);

            return;
        }

        if (array_key_exists('limitType', $config) && !array_key_exists('maxType', $config)) {
            $config['maxType'] = $config['limitType'];
        }

        if (array_key_exists('limitAmount', $config) && !array_key_exists('max', $config)) {
            $config['max'] = $config['limitAmount'];
        }

        unset($config['limitType'], $config['limitAmount']);
    }

    private static function _removeUnsupportedLimitConfig(array &$config, string $fieldClass): void
    {
        if (array_key_exists('limit', $config) && !in_array($fieldClass, self::_supportedLimitTypes(), true)) {
            unset($config['limit']);
        }

        if (array_key_exists('maxType', $config) && !in_array($fieldClass, self::_supportedLimitConfigTypes(), true)) {
            unset($config['maxType']);
        }
    }

    private static function _normalizeLegacyPositions(array &$config): void
    {
        if (!array_key_exists('instructionsPosition', $config)) {
            return;
        }

        if ($config['instructionsPosition'] === 'verbb\\formie\\positions\\FieldsetStart') {
            $config['instructionsPosition'] = AboveInput::class;
        }

        if ($config['instructionsPosition'] === 'verbb\\formie\\positions\\FieldsetEnd') {
            $config['instructionsPosition'] = BelowInput::class;
        }
    }

    private static function _normalizeLegacyFieldConfig(array &$config): void
    {
        if (array_key_exists('includeInEmail', $config)) {
            if (!array_key_exists('includeInEmailFieldSummaries', $config)) {
                $config['includeInEmailFieldSummaries'] = (bool)ArrayHelper::remove($config, 'includeInEmail');
            } else {
                unset($config['includeInEmail']);
            }
        }

        if (array_key_exists('emailValue', $config)) {
            if (!array_key_exists('emailFieldSummaryValue', $config)) {
                $config['emailFieldSummaryValue'] = (string)ArrayHelper::remove($config, 'emailValue');
            } else {
                unset($config['emailValue']);
            }
        }

        if (array_key_exists('subfieldLabelPosition', $config)) {
            $config['subFieldLabelPosition'] = ArrayHelper::remove($config, 'subfieldLabelPosition');
        }
    }

    private static function _normalizeValidationMessages(array &$config, string $fieldClass): void
    {
        if (in_array($fieldClass, self::_childFieldTypes(), true)) {
            unset($config['errorMessage'], $config['validationMessages']);

            return;
        }

        if (!isset($config['validationMessages']) || !is_array($config['validationMessages'])) {
            $config['validationMessages'] = [];
        }

        $legacyRequired = trim((string)($config['errorMessage'] ?? ''));

        if ($legacyRequired !== '' && empty($config['validationMessages']['required'])) {
            $config['validationMessages']['required'] = self::_normalizeValidationMessageTokens($legacyRequired);
        }

        foreach ($config['validationMessages'] as $key => $message) {
            if (!is_string($message) || $message === '') {
                continue;
            }

            $config['validationMessages'][$key] = self::_normalizeValidationMessageTokens($message);
        }

        $required = trim((string)($config['validationMessages']['required'] ?? ''));

        if ($required !== '') {
            $config['errorMessage'] = $required;
        }
    }

    private static function _normalizeValidationMessageTokens(string $message): string
    {
        return str_replace(['{name}', '{attribute}'], '{label}', $message);
    }

    private static function _normalizeLegacyComboboxConfig(array &$config, string $fieldClass): void
    {
        if ($fieldClass === fields\Phone::class) {
            if (isset($config['countryAllowed']) && is_array($config['countryAllowed'])) {
                // Vue comboboxes stored selected option payloads; React comboboxes store values.
                $config['countryAllowed'] = self::_normalizeComboboxMultiValue($config['countryAllowed']);
            }

            if (isset($config['countryDefaultValue'])) {
                $config['countryDefaultValue'] = self::_normalizeComboboxSingleValue($config['countryDefaultValue']);
            }
        }

        if ($fieldClass === fields\Address::class && isset($config['countryAllowed']) && is_array($config['countryAllowed'])) {
            $config['countryAllowed'] = self::_normalizeComboboxMultiValue($config['countryAllowed']);
        }

        if ($fieldClass === fields\subfields\AddressCountry::class && isset($config['defaultValue'])) {
            $config['defaultValue'] = self::_normalizeComboboxSingleValue($config['defaultValue']);
        }

        if ($fieldClass === fields\subfields\AddressAutoComplete::class && isset($config['countryDefaultValue'])) {
            $config['countryDefaultValue'] = self::_normalizeComboboxSingleValue($config['countryDefaultValue']);
        }
    }

    private static function _normalizeComboboxMultiValue(array $value): array
    {
        $values = [];

        foreach ($value as $item) {
            $normalized = self::_normalizeComboboxSingleValue($item);

            if ($normalized !== null && $normalized !== '') {
                $values[] = $normalized;
            }
        }

        return $values;
    }

    private static function _normalizeComboboxSingleValue(mixed $value): mixed
    {
        if (is_array($value) && array_key_exists('value', $value)) {
            return $value['value'];
        }

        return $value;
    }

    private static function _normalizeOptionsFieldConfig(array &$config, string $fieldClass): void
    {
        if (!in_array($fieldClass, self::_optionsFieldTypes(), true)) {
            return;
        }

        $config['optionsMode'] = OptionsMode::normalize($config['optionsMode'] ?? null);

        if (in_array($config['optionsMode'], [OptionsMode::STATIC, OptionsMode::TEMPLATE], true)) {
            unset($config['optionSource']);

            return;
        }

        self::_normalizeOptionSourceConfig($config, OptionSourceConfigHelper::allowedTypesForFieldClass($fieldClass));
    }

    private static function _normalizeRecipientsFieldConfig(array &$config, string $fieldClass): void
    {
        if ($fieldClass !== fields\Recipients::class) {
            return;
        }

        $config['optionsMode'] = OptionsMode::normalize($config['optionsMode'] ?? null);

        if (in_array($config['optionsMode'], [OptionsMode::STATIC, OptionsMode::TEMPLATE], true)) {
            unset($config['optionSource']);

            return;
        }

        self::_normalizeOptionSourceConfig($config, OptionSourceConfigHelper::allowedTypesForFieldClass($fieldClass));
    }

    private static function _normalizeOptionSourceConfig(array &$config, array $allowedTypes): void
    {
        if (($config['optionsMode'] ?? null) !== OptionsMode::DYNAMIC) {
            return;
        }

        $optionSource = $config['optionSource'] ?? null;

        if (is_string($optionSource)) {
            $optionSource = Json::decodeIfJson($optionSource);
        }

        $optionSource = OptionSourceConfigHelper::normalizeOptionSource($optionSource, $config['optionsMode'], $allowedTypes);

        if ($optionSource === null) {
            unset($config['optionSource']);
            $config['optionsMode'] = OptionsMode::STATIC;

            return;
        }

        $config['optionSource'] = $optionSource;
    }

    private static function _normalizeFieldAttributes(array &$config): void
    {
        foreach ([FieldAttributesHelper::SETTING_CONTAINER, FieldAttributesHelper::SETTING_INPUT] as $setting) {
            if (!array_key_exists($setting, $config)) {
                continue;
            }

            $config[$setting] = FieldAttributesHelper::normalize($config[$setting], $setting, false);
        }
    }

    private static function _removeLegacyProperties(array &$config): void
    {
        foreach (self::_removedProperties() as $removedProperty) {
            unset($config[$removedProperty]);
        }
    }

    private static function _supportedLimitConfigTypes(): array
    {
        return [
            fields\MultiLineText::class,
            fields\SingleLineText::class,
        ];
    }

    private static function _supportedLimitTypes(): array
    {
        return [
            fields\Categories::class,
            fields\Entries::class,
            fields\FileUpload::class,
            fields\MultiLineText::class,
            fields\Number::class,
            fields\Products::class,
            fields\SingleLineText::class,
            fields\Tags::class,
            fields\Users::class,
            fields\Variants::class,
            fields\subfields\AddressAutoComplete::class,
            fields\subfields\Address1::class,
            fields\subfields\Address2::class,
            fields\subfields\Address3::class,
            fields\subfields\AddressCity::class,
            fields\subfields\DateDate::class,
            fields\subfields\DateTime::class,
            fields\subfields\AddressZip::class,
            fields\subfields\AddressState::class,
            fields\subfields\AddressCountry::class,
            fields\subfields\DateYearDropdown::class,
            fields\subfields\DateMonthDropdown::class,
            fields\subfields\DateDayDropdown::class,
            fields\subfields\DateHourDropdown::class,
            fields\subfields\DateMinuteDropdown::class,
            fields\subfields\DateSecondDropdown::class,
            fields\subfields\DateAmPmDropdown::class,
            fields\subfields\DateYearNumber::class,
            fields\subfields\DateMonthNumber::class,
            fields\subfields\DateDayNumber::class,
            fields\subfields\DateHourNumber::class,
            fields\subfields\DateMinuteNumber::class,
            fields\subfields\DateSecondNumber::class,
            fields\subfields\DateAmPmNumber::class,
            fields\subfields\NamePrefix::class,
            fields\subfields\NameFirst::class,
            fields\subfields\NameMiddle::class,
            fields\subfields\NameLast::class,
        ];
    }

    private static function _childFieldTypes(): array
    {
        return array_values(array_filter(
            self::_supportedLimitTypes(),
            static fn(string $class): bool => str_contains($class, '\\subfields\\'),
        ));
    }

    private static function _optionsFieldTypes(): array
    {
        return [
            fields\Checkboxes::class,
            fields\Dropdown::class,
            fields\Radio::class,
        ];
    }

    private static function _removedProperties(): array
    {
        return [
            'vid',
            'brandNewField',
            'hasLabel',
            'isNested',
            'isSynced',
            'allowSelfRelations',
            'localizeRelations',
            'minRelations',
            'maxRelations',
            'selectionLabel',
            'showSiteMenu',
            'targetSiteId',
            'validateRelatedElements',
            'viewMode',
            'maintainHierarchy',
            'branchLimit',
            'restrictedLocationSource',
            'restrictedLocationSubpath',
            'allowSubfolders',
            'restrictedDefaultUploadSubpath',
            'defaultUploadLocationSource',
            'defaultUploadLocationSubpath',
            'allowUploads',
            'showUnpermittedVolumes',
            'showUnpermittedFiles',
            'previewMode',
            'showCardsInGrid',
            'useSingleFolder',
            'singleUploadLocationSource',
            'singleUploadLocationSubpath',
            'allowLimit',
            'enableAutocomplete',
            'rowUid',
            'formId',
            'searchable',
            'translationMethod',
            'translationKeyFormat',
            'rowsConfig',
            'columnType',
        ];
    }
}
