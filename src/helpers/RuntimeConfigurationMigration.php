<?php
namespace verbb\formie\helpers;

final class RuntimeConfigurationMigration
{
    // Static Methods
    // =========================================================================

    public static function migrate(array $data, ?string $type = null): array
    {
        $type = $data['type'] ?? $type;
        if (array_key_exists('prePopulate', $data)) {
            if (!array_key_exists('prefillQueryParam', $data)) {
                $data['prefillQueryParam'] = $data['prePopulate'];
            }
            unset($data['prePopulate']);
        }
        if ($type === \verbb\formie\fields\Hidden::class && array_key_exists('defaultOption', $data)) {
            $data['valueSource'] ??= $data['defaultOption'];
            unset($data['defaultOption']);
        }
        if (isset($data['submitAction']) && in_array($data['submitAction'], ['message', 'entry', 'url', 'reload', 'reset'], true)) {
            $action = $data['submitAction'];
            $data['completionBehavior'] ??= in_array($action, ['entry', 'url'], true) ? 'redirect' : $action;
            $data['completionRedirectSource'] ??= $action === 'entry' ? 'entry' : 'url';
        }
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::migrate($value, $key === 'settings' ? $type : null);
            }
        }
        return $data;
    }
}
