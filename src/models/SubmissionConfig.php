<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\helpers\RuntimeConfigurationMigration;
use verbb\formie\services\RuntimeConfiguration;

use InvalidArgumentException;

final class SubmissionConfig
{
    // Static Methods
    // =========================================================================

    public static function decode(array $data, Form $form): FormInstanceConfig
    {
        if ($data === []) {
            return new FormInstanceConfig();
        }

        if (!array_key_exists('version', $data)) {
            return self::_decodeFormie3($data, $form);
        }

        if ($data['version'] !== 1) {
            throw new InvalidArgumentException('Unsupported submission configuration version.');
        }

        $fields = [];

        foreach ((array)($data['fields'] ?? []) as $identity => $settings) {
            $field = self::_findFieldByUid($form, (string)$identity);

            if ($field) {
                $fields[$field->uid] = self::_filterFieldSettings($field, (array)$settings);
            }
        }

        $formSettings = self::_filterFormSettings((array)($data['form'] ?? []));

        return new FormInstanceConfig(
            $formSettings,
            $fields,
            (array)($data['pages'] ?? []),
            (array)($data['initial'] ?? []),
            (array)($data['forced'] ?? []),
            (array)($data['query'] ?? []),
            (array)($data['prefill'] ?? []),
            isset($data['completionRedirectOverride']) ? (string)$data['completionRedirectOverride'] : null
        );
    }

    public static function capture(FormInstanceConfig $config): array
    {
        $data = $config->toArray();
        $data['form'] = array_intersect_key($data['form'], array_flip(RuntimeConfiguration::DURABLE_FORM_SETTINGS));

        foreach ($data['fields'] as $uid => $settings) {
            $data['fields'][$uid] = array_diff_key($settings, array_flip(['cssClasses', 'containerAttributes', 'inputAttributes', 'placeholder']));
        }

        if (!array_filter(array_diff_key($data, ['version' => true]))) {
            return [];
        }
        return $data;
    }

    private static function _decodeFormie3(array $data, Form $form): FormInstanceConfig
    {
        $fields = [];

        foreach ((array)($data['fields'] ?? []) as $handle => $settings) {
            $field = self::_findFieldByHandle($form, (string)$handle);

            if ($field) {
                $settings = RuntimeConfigurationMigration::migrate((array)$settings, get_class($field));
                $fields[$field->uid] = self::_filterFieldSettings($field, $settings);
            }
        }

        $formSettings = RuntimeConfigurationMigration::migrate((array)($data['form'] ?? []));

        // Formie 3 snapshots contained only form and handle-keyed field settings.
        // Do not interpret unversioned Formie 4 beta sections as current config.
        return new FormInstanceConfig(self::_filterFormSettings($formSettings), $fields);
    }

    private static function _findFieldByUid(Form $form, string $uid): ?Field
    {
        foreach ($form->getFieldsRecursively() as $field) {
            if ($field->uid === $uid) {
                return $field;
            }
        }

        return null;
    }

    private static function _findFieldByHandle(Form $form, string $path): ?Field
    {
        $handles = explode('.', $path);
        $handle = array_shift($handles);

        if (!$handle) {
            return null;
        }

        $field = $form->getFieldByHandle($handle);

        foreach ($handles as $handle) {
            if (!$field instanceof ParentFieldInterface) {
                return null;
            }

            $field = $field->getFieldByHandle($handle);
        }

        return $field instanceof Field ? $field : null;
    }

    private static function _filterFieldSettings(Field $field, array $settings): array
    {
        return array_intersect_key($settings, array_flip($field->runtimeOverridableSettings()));
    }

    private static function _filterFormSettings(array $settings): array
    {
        $formSettings = array_intersect_key($settings, array_flip(RuntimeConfiguration::FORM_SETTINGS));

        if (isset($settings['integrations'])) {
            $formSettings['integrations'] = Formie::$plugin->getIntegrations()->filterAllIntegrationFormSettings($settings['integrations'], false);
        }

        return $formSettings;
    }
}
