<?php
namespace verbb\formie\helpers;

use Craft;

/** Decides which integration settings a user who isn't an admin can change. */
final class IntegrationSettingsPolicy
{
    // Static Methods
    // =========================================================================

    /**
     * Returns why the posted settings can't be saved, or `null` when they can.
     *
     * @param array $posted Settings as posted, before they're merged over the saved ones.
     * @param array $stored The saved settings, or an empty array for a new integration.
     * @param string[] $sensitive Settings that hold credentials.
     * @param bool $hiddenCredentials Whether saved credentials are masked in the form, so carrying one over hides its value.
     * @param bool $providerChanged Whether the saved settings are moving to another provider type.
     */
    public static function violation(array $posted, array $stored, array $sensitive = [], bool $hiddenCredentials = false, bool $providerChanged = false): ?string
    {
        if (self::_isTrusted()) {
            return null;
        }

        // Environment variables can hold any server secret, such as the database password, so only admins can
        // point a setting at one. A variable an admin already saved can stay.
        foreach (self::_environmentReferences($posted) as [$path, $value]) {
            if (self::_valueAt($stored, $path) !== $value) {
                return Craft::t('formie', 'Only admins can use environment variables in integration settings.');
            }
        }

        if (!$stored || (!$providerChanged && !self::_destinationChanged($posted, $stored))) {
            return null;
        }

        // Saved credentials the user can't see only go where an admin pointed them.
        foreach ($sensitive as $attribute) {
            $saved = $stored[$attribute] ?? null;

            if ($saved === null || $saved === '') {
                continue;
            }

            $carried = !array_key_exists($attribute, $posted) || $posted[$attribute] === $saved;

            if ($carried && ($hiddenCredentials || self::_environmentReferences([$saved]))) {
                return Craft::t('formie', 'Enter the credentials again after changing where this integration connects.');
            }
        }

        return null;
    }


    // Private Methods
    // =========================================================================

    private static function _isTrusted(): bool
    {
        return Craft::$app->getRequest()->getIsConsoleRequest() || Craft::$app->getUser()->getIsAdmin();
    }

    private static function _destinationChanged(array $posted, array $stored): bool
    {
        foreach ($posted as $key => $value) {
            if (is_string($key) && preg_match(self::DESTINATION_SETTING, $key) && (string)($stored[$key] ?? '') !== (is_scalar($value) ? (string)$value : '')) {
                return true;
            }
        }

        return false;
    }

    private static function _environmentReferences(array $settings, array $path = []): array
    {
        $references = [];

        foreach ($settings as $key => $value) {
            if (is_array($value)) {
                array_push($references, ...self::_environmentReferences($value, [...$path, $key]));
            } elseif (is_string($value) && (preg_match('/\$\{\w+}/', $value) || preg_match('/(?<=^|\/)\$\w+(?=$|\/)/', $value))) {
                // The same `${VAR}`, `$VAR` and `/$VAR/` forms that `App::parseEnv()` resolves.
                $references[] = [[...$path, $key], $value];
            }
        }

        return $references;
    }

    private static function _valueAt(array $settings, array $path): mixed
    {
        foreach ($path as $key) {
            if (!is_array($settings) || !array_key_exists($key, $settings)) {
                return null;
            }

            $settings = $settings[$key];
        }

        return $settings;
    }


    // Constants
    // =========================================================================

    // Settings that decide which server an integration sends its credentials to, such as `apiUrl` or `apiDomain`.
    private const DESTINATION_SETTING = '/(url|uri|domain|host|endpoint|server)$/i';
}
