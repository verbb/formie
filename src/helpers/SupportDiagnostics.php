<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Form;
use verbb\formie\models\MissingIntegration;

use Craft;
use craft\helpers\App;

use Throwable;

class SupportDiagnostics
{
    // Static Methods
    // =========================================================================

    public static function collect(Form $form): array
    {
        $config = Craft::$app->getConfig();
        $general = $config->getGeneral();
        $user = Craft::$app->getUser()->getIdentity();
        $permissions = Formie::$plugin->getPermissions();
        $settingsPermissions = [];

        foreach (array_keys($permissions->getSettingsPageDefinitions()) as $page) {
            $settingsPermissions[$page] = $permissions->canAccessSettingsPage($user, $page);
        }

        // Build an allowlisted snapshot; never serialize config, user or integration objects.
        $diagnostics = [
            'schemaVersion' => 1,
            'collectedAt' => gmdate('Y-m-d\TH:i:s\Z'),
            'installation' => [
                'environment' => $config->env,
                'hostname' => Craft::$app->getRequest()->getHostName(),
                'phpVersion' => PHP_VERSION,
                'craftVersion' => Craft::$app->getVersion(),
                'formieVersion' => Formie::$plugin->getVersion(),
                'devMode' => $general->devMode,
                'allowAdminChanges' => $general->allowAdminChanges,
            ],
            'user' => [
                'admin' => (bool)($user?->admin),
                'canAccessForms' => $permissions->canAccessForms($user),
                'canViewForm' => $permissions->canViewForm($user, $form),
                'canManageForm' => $permissions->canManageForm($user, $form),
                'canAccessIntegrations' => $permissions->canAccessIntegrations($user),
                'settingsPages' => $settingsPermissions,
            ],
            'form' => [
                'handle' => $form->handle,
                'disableCaptchas' => (bool)$form->settings->disableCaptchas,
            ],
            'integrations' => [],
            'collectionErrors' => [],
        ];

        $service = Formie::$plugin->getIntegrations();
        $formSettings = $form->settings->integrations ?? [];
        $providers = [];

        try {
            foreach ($service->getAllCaptchas() as $provider) {
                $providers[$provider->handle] = $provider;
            }
        } catch (Throwable) {
            // Exception text can contain credentials, URLs or configuration values.
            $diagnostics['collectionErrors'][] = 'captcha-providers-unavailable';
        }

        foreach (array_keys($formSettings) as $handle) {
            if (array_key_exists($handle, $providers)) {
                continue;
            }

            try {
                $providers[$handle] = $service->getIntegrationByHandle((string)$handle);
            } catch (Throwable) {
                $diagnostics['integrations'][$handle] = ['status' => 'unavailable'];
            }
        }

        foreach ($providers as $handle => $provider) {
            try {
                $diagnostics['integrations'][$handle] = self::_provider($provider, (bool)($formSettings[$handle]['enabled'] ?? false));
            } catch (Throwable) {
                $diagnostics['integrations'][$handle] = ['status' => 'unavailable'];
            }
        }

        return $diagnostics;
    }

    private static function _provider(?IntegrationInterface $provider, bool $enabledForForm): array
    {
        $result = [
            'status' => $provider && !$provider instanceof MissingIntegration ? 'available' : 'missing',
            'enabledForForm' => $enabledForForm,
        ];

        if (!$provider || $provider instanceof MissingIntegration) {
            return $result;
        }

        $result['type'] = get_class($provider);
        $result['stored'] = $provider->id !== null;
        $result['scope'] = $provider->getScope();
        $result['enabledGlobally'] = (bool)$provider->getEnabled();
        $result['credentials'] = [];
        $attributes = IntegrationSecrets::sensitiveAttributes($provider);

        // Public keys and OAuth client IDs are useful to diagnose but their values are unnecessary.
        foreach (['siteKey', 'clientId'] as $attribute) {
            if (property_exists($provider, $attribute)) {
                $attributes[] = $attribute;
            }
        }

        foreach (array_unique($attributes) as $attribute) {
            $result['credentials'][$attribute] = self::_credential($provider->$attribute);
        }

        return $result;
    }

    private static function _credential(mixed $value): array
    {
        $configured = is_string($value) ? trim($value) !== '' : !empty($value);
        $environmentReference = is_string($value) && (bool)preg_match('/\$(?:\w+|\{\w+\})/', $value);
        $resolved = null;

        // Non-scalar credentials are deliberately not traversed or copied into diagnostics.
        if (is_string($value) || $value === null) {
            try {
                $parsed = App::parseEnv($value);
                $resolved = !IntegrationSecrets::containsUnavailable($value)
                    && is_string($parsed) && trim($parsed) !== '';
            } catch (Throwable) {
                $resolved = false;
            }
        }

        return [
            'configured' => $configured,
            'usesEnvironmentVariable' => $environmentReference,
            'resolvesToNonEmptyValue' => $resolved,
        ];
    }
}
