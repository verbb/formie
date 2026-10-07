<?php
namespace verbb\formie\helpers;

use verbb\formie\fields\Hidden;

final class RuntimeConfigurationMigration
{
    // Static Methods
    // =========================================================================

    public static function migrate(array $data, ?string $type = null): array
    {
        $type = $data['type'] ?? $type;

        return self::_migrate($data, $type, $type === null);
    }

    private static function _migrate(array $data, ?string $type, bool $completionSettings): array
    {
        $aliases = [
            'submitActionUrl' => 'redirectUrl',
            'submitActionTab' => 'redirectTarget',
            'submitActionFormHide' => 'hideFormAfterSubmit',
            'submitActionMessage' => 'successMessage',
            'submitActionMessageTimeout' => 'successMessageTimeout',
            'submitActionMessagePosition' => 'successMessagePosition',
            'submitActionEntry' => 'redirectEntry',
        ];

        if ($completionSettings) {
            foreach ($aliases as $legacy => $canonical) {
                $canonicalMissing = !array_key_exists($canonical, $data);

                if ($legacy === 'submitActionUrl') {
                    $canonicalMissing = $canonicalMissing || $data[$canonical] === null || $data[$canonical] === '';
                }

                if (array_key_exists($legacy, $data) && $canonicalMissing) {
                    $data[$canonical] = $data[$legacy];
                }
                unset($data[$legacy]);
            }
        }

        if (array_key_exists('prePopulate', $data)) {
            if (!array_key_exists('prefillQueryParam', $data)) {
                $data['prefillQueryParam'] = $data['prePopulate'];
            }
            unset($data['prePopulate']);
        }

        if ($type === Hidden::class && array_key_exists('defaultOption', $data)) {
            $data['valueSource'] ??= $data['defaultOption'];
            unset($data['defaultOption']);
        }

        if ($completionSettings && isset($data['submitAction']) && in_array($data['submitAction'], ['message', 'entry', 'url', 'reload', 'reset'], true)) {
            $action = $data['submitAction'];
            $data['completionBehavior'] ??= in_array($action, ['entry', 'url'], true) ? 'redirect' : $action;
            $data['completionRedirectSource'] ??= $action === 'entry' ? 'entry' : 'url';
            unset($data['submitAction']);
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $childType = $key === 'settings' ? $type : null;
                $childCompletionSettings = $type === null && in_array($key, ['form', 'settings'], true);
                $data[$key] = self::_migrate($value, $childType, $childCompletionSettings);
            }
        }
        return $data;
    }
}
