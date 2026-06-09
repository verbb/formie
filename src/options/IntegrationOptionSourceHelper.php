<?php
namespace verbb\formie\options;

use verbb\formie\Formie;
use verbb\formie\base\IntegrationInterface;

use Craft;

class IntegrationOptionSourceHelper
{
    // Static Methods
    // =========================================================================

    public static function getProviderHandleForIntegrationClass(string $integrationClass): ?string
    {
        foreach (self::_getConfiguredSourceDefinitions() as $definition) {
            if (($definition['integrationClass'] ?? null) === $integrationClass) {
                return $definition['handle'];
            }
        }

        return null;
    }

    public static function getProviderIntegrationClass(string $provider): ?string
    {
        return self::_getProviderDefinition($provider)['integrationClass']
            ?? self::_getRegisteredProviderDefinition($provider)['integrationClass']
            ?? null;
    }

    public static function providerExists(string $provider): bool
    {
        return self::_getRegisteredProviderDefinition($provider) !== null
            || self::_getProviderDefinition($provider) !== null;
    }

    public static function hasEnabledIntegrationOptionSources(): bool
    {
        return self::getEnabledIntegrationInstanceOptions() !== [];
    }

    public static function getEnabledIntegrationInstanceOptions(): array
    {
        $options = [];
        $seen = [];

        foreach (self::_getConfiguredSourceDefinitions(true) as $definition) {
            $integration = $definition['integration'];
            $integrationId = (int)$integration->id;

            if (isset($seen[$integrationId])) {
                continue;
            }

            $seen[$integrationId] = true;

            $options[] = [
                'label' => $integration->name,
                'value' => $integrationId,
                'handle' => (string)$integration->handle,
            ];
        }

        return $options;
    }

    public static function getProviderOptionsForIntegration(int $integrationId): array
    {
        $options = [];

        foreach (self::_getConfiguredSourceDefinitions(true) as $definition) {
            if ((int)$definition['integration']->id !== $integrationId) {
                continue;
            }

            $options[] = [
                'label' => (string)$definition['label'],
                'value' => (string)$definition['handle'],
            ];
        }

        return $options;
    }

    public static function getProviderOptions(): array
    {
        $options = [];
        $seen = [];

        foreach (self::_getConfiguredSourceDefinitions(true) as $definition) {
            $provider = (string)$definition['handle'];

            if (isset($seen[$provider])) {
                continue;
            }

            $options[] = [
                'label' => (string)$definition['label'],
                'value' => $provider,
            ];
            $seen[$provider] = true;
        }

        return $options;
    }

    public static function getIntegrationOptions(string $provider): array
    {
        $options = [];

        foreach (self::_getConfiguredSourceDefinitions(true) as $definition) {
            if ($definition['handle'] !== $provider) {
                continue;
            }

            $integration = $definition['integration'];

            $options[] = [
                'label' => $integration->name,
                'value' => (int)$integration->id,
            ];
        }

        return $options;
    }

    public static function getBuilderConfigForIntegration(int $integrationId): array
    {
        $integration = Formie::$plugin->getIntegrations()->getIntegrationById($integrationId);

        if (!$integration instanceof IntegrationInterface) {
            return [
                'error' => Craft::t('formie', 'Integration not found.'),
            ];
        }

        if (!$integration->getEnabled()) {
            return [
                'error' => Craft::t('formie', 'Integration is disabled.'),
            ];
        }

        $definitions = self::_getIntegrationSourceDefinitions($integration);

        if (!$definitions) {
            return [
                'error' => Craft::t('formie', 'This integration does not support dynamic options.'),
            ];
        }

        return self::getBuilderConfig((string)$definitions[0]['handle'], $integrationId);
    }

    public static function getBuilderConfig(string $provider, int $integrationId): array
    {
        if (!self::_getProviderDefinition($provider)) {
            return [
                'error' => Craft::t('formie', 'Unknown integration option provider.'),
            ];
        }

        $integration = Formie::$plugin->getIntegrations()->getIntegrationById($integrationId);

        if (!$integration instanceof IntegrationInterface || !self::_integrationSupportsProvider($integration, $provider)) {
            return [
                'error' => Craft::t('formie', 'Integration not found.'),
            ];
        }

        if (!$integration->getEnabled()) {
            return [
                'error' => Craft::t('formie', 'Integration is disabled.'),
            ];
        }

        $config = method_exists($integration, 'getOptionSourceBuilderConfig')
            ? $integration->getOptionSourceBuilderConfig($provider)
            : [
                'error' => Craft::t('formie', 'This integration does not support dynamic options.'),
            ];

        return [
            'provider' => $provider,
            'integrationId' => $integrationId,
            'integrationOptions' => self::getEnabledIntegrationInstanceOptions(),
            'providerOptions' => self::getProviderOptionsForIntegration($integrationId),
            'refreshParams' => self::_getOptionSourceRefreshParams($integration, $provider),
            ...$config,
        ];
    }

    public static function resolveOptions(string $provider, array $params = []): OptionList
    {
        $integrationId = (int)($params['integrationId'] ?? 0);
        if (!$integrationId) {
            return OptionList::error(Craft::t('formie', 'Select an integration.'));
        }

        if (!self::_getProviderDefinition($provider)) {
            return OptionList::error(Craft::t('formie', 'Unknown integration option provider.'));
        }

        $integration = Formie::$plugin->getIntegrations()->getIntegrationById($integrationId);

        if (!$integration instanceof IntegrationInterface || !self::_integrationSupportsProvider($integration, $provider)) {
            return OptionList::error(Craft::t('formie', 'Integration not found.'));
        }

        if (!$integration->getEnabled()) {
            return OptionList::error(Craft::t('formie', 'Integration is disabled.'));
        }

        return method_exists($integration, 'resolveOptionSourceOptions')
            ? $integration->resolveOptionSourceOptions($provider, $params)
            : OptionList::error(Craft::t('formie', 'This integration does not support dynamic options.'));
    }

    public static function flattenIntegrationFieldOptions(array $options): array
    {
        if (isset($options['options']) && is_array($options['options'])) {
            $options = [$options];
        }

        $rows = [];

        foreach ($options as $option) {
            if (!is_array($option)) {
                continue;
            }

            if (isset($option['options']) && is_array($option['options'])) {
                foreach (self::flattenIntegrationFieldOptions($option['options']) as $nested) {
                    $rows[] = $nested;
                }

                continue;
            }

            $label = trim((string)($option['label'] ?? ''));
            $value = trim((string)($option['value'] ?? ''));

            if ($label === '' && $value === '') {
                continue;
            }

            $rows[] = [
                'label' => $label !== '' ? $label : $value,
                'value' => $value !== '' ? $value : $label,
            ];
        }

        return $rows;
    }

    private static function _getProviderDefinition(string $provider, bool $enabledOnly = false): ?array
    {
        foreach (self::_getConfiguredSourceDefinitions($enabledOnly) as $definition) {
            if ($definition['handle'] === $provider) {
                return $definition;
            }
        }

        return null;
    }

    private static function _getRegisteredProviderDefinition(string $provider): ?array
    {
        foreach (self::_getRegisteredSourceDefinitions() as $definition) {
            if ($definition['handle'] === $provider) {
                return $definition;
            }
        }

        return null;
    }

    private static function _integrationSupportsProvider(IntegrationInterface $integration, string $provider): bool
    {
        foreach (self::_getIntegrationSourceDefinitions($integration) as $definition) {
            if ($definition['handle'] === $provider) {
                return true;
            }
        }

        return false;
    }

    private static function _getConfiguredSourceDefinitions(bool $enabledOnly = false): array
    {
        $definitions = [];

        if (!Formie::$plugin) {
            return $definitions;
        }

        foreach (Formie::$plugin->getIntegrations()->getAllIntegrations() as $integration) {
            if (!$integration instanceof IntegrationInterface) {
                continue;
            }

            if ($enabledOnly && !$integration->getEnabled()) {
                continue;
            }

            foreach (self::_getIntegrationSourceDefinitions($integration) as $definition) {
                $definitions[] = [
                    ...$definition,
                    'integration' => $integration,
                    'integrationClass' => get_class($integration),
                ];
            }
        }

        return $definitions;
    }

    private static function _getRegisteredSourceDefinitions(): array
    {
        $definitions = [];

        if (!Formie::$plugin) {
            return $definitions;
        }

        foreach (Formie::$plugin->getIntegrations()->getAllIntegrationTypes() as $integrationClasses) {
            foreach ($integrationClasses as $integrationClass) {
                if (!is_subclass_of($integrationClass, IntegrationInterface::class)) {
                    continue;
                }

                foreach ($integrationClass::getOptionSourceDefinitions() as $definition) {
                    $definitions[] = [
                        ...$definition,
                        'integrationClass' => $integrationClass,
                    ];
                }
            }
        }

        return $definitions;
    }

    private static function _getIntegrationSourceDefinitions(IntegrationInterface $integration): array
    {
        if (!method_exists($integration, 'getOptionSourceDefinitions')) {
            return [];
        }

        $integrationClass = get_class($integration);

        return $integrationClass::getOptionSourceDefinitions();
    }

    private static function _getOptionSourceRefreshParams(IntegrationInterface $integration, string $provider): array
    {
        if (method_exists($integration, 'getOptionSourceRefreshParams')) {
            $params = $integration->getOptionSourceRefreshParams($provider);

            if (is_array($params)) {
                return $params;
            }
        }

        if (method_exists($integration, 'getFormSettingsRefreshParams')) {
            $params = $integration->getFormSettingsRefreshParams();

            if (is_array($params)) {
                return $params;
            }
        }

        return [];
    }
}
