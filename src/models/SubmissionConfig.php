<?php
namespace verbb\formie\models;

use verbb\formie\elements\Form;
use verbb\formie\services\RuntimeConfiguration;

final class SubmissionConfig
{
    // Static Methods
    // =========================================================================

    public static function decode(array $data, Form $form): FormInstanceConfig
    {
        if ($data === []) {
            return new FormInstanceConfig();
        }
        if (isset($data['version']) && $data['version'] !== 1) {
            throw new \InvalidArgumentException('Unsupported submission configuration version.');
        }
        $runtime = new RuntimeConfiguration();
        $fields = [];
        foreach ((array)($data['fields'] ?? []) as $identity => $settings) {
            $field = $runtime->findField($form, (string)$identity, false);
            if ($field) {
                // Legacy beta snapshots were handle keyed and could contain arbitrary
                // properties. Decode only today's allowlisted durable subset.
                $settings = \verbb\formie\helpers\RuntimeConfigurationMigration::migrate((array)$settings, get_class($field));
                $fields[$field->uid] = array_intersect_key($settings, array_flip($field->runtimeOverridableSettings()));
            }
        }
        $formSettings = array_intersect_key((array)($data['form'] ?? []), array_flip(RuntimeConfiguration::FORM_SETTINGS));
        if (isset($data['form']['integrations'])) {
            $formSettings['integrations'] = \verbb\formie\Formie::$plugin->getIntegrations()->filterAllIntegrationFormSettings($data['form']['integrations'], false);
        }
        if (isset($formSettings['submitAction']) && !isset($formSettings['completionBehavior'])) {
            $action = $formSettings['submitAction'];
            $formSettings['completionBehavior'] = in_array($action, ['entry', 'url'], true) ? 'redirect' : $action;
            $formSettings['completionRedirectSource'] = $action === 'entry' ? 'entry' : 'url';
        }
        return new FormInstanceConfig($formSettings, $fields, (array)($data['pages'] ?? []),
            (array)($data['initial'] ?? []), (array)($data['forced'] ?? []),
            (array)($data['query'] ?? []), (array)($data['prefill'] ?? []));
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
}
