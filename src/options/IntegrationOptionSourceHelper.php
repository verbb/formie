<?php
namespace verbb\formie\options;

use verbb\formie\Formie;
use verbb\formie\base\IntegrationInterface;

use Craft;

class IntegrationOptionSourceHelper
{
    // Constants
    // =========================================================================

    public const USAGE_OPTIONS = 'options';
    public const USAGE_RECIPIENTS = 'recipients';


    // Static Methods
    // =========================================================================

    public static function getProviderHandleForIntegrationClass(string $integrationClass, ?string $usage = null): ?string
    {
        foreach (self::_getConfiguredSourceDefinitions(false, $usage) as $definition) {
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

    public static function providerExists(string $provider, ?string $usage = null): bool
    {
        return self::_getRegisteredProviderDefinition($provider, $usage) !== null
            || self::_getProviderDefinition($provider, false, $usage) !== null;
    }

    public static function hasEnabledIntegrationOptionSources(?string $usage = null): bool
    {
        return self::getEnabledIntegrationInstanceOptions($usage) !== [];
    }

    public static function getEnabledIntegrationInstanceOptions(?string $usage = null): array
    {
        $options = [];
        $seen = [];

        foreach (self::_getConfiguredSourceDefinitions(true, $usage) as $definition) {
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

    public static function getProviderOptionsForIntegration(int $integrationId, ?string $usage = null): array
    {
        $options = [];

        foreach (self::_getConfiguredSourceDefinitions(true, $usage) as $definition) {
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

    public static function getProviderOptions(?string $usage = null): array
    {
        $options = [];
        $seen = [];

        foreach (self::_getConfiguredSourceDefinitions(true, $usage) as $definition) {
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

    public static function getIntegrationOptions(string $provider, ?string $usage = null): array
    {
        $options = [];

        foreach (self::_getConfiguredSourceDefinitions(true, $usage) as $definition) {
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

    public static function getBuilderConfigForIntegration(int $integrationId, ?string $usage = null): array
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

        $definitions = self::_getIntegrationSourceDefinitions($integration, $usage);

        if (!$definitions) {
            return [
                'error' => Craft::t('formie', 'This integration does not support dynamic options.'),
            ];
        }

        return self::getBuilderConfig((string)$definitions[0]['handle'], $integrationId, $usage);
    }

    public static function getBuilderConfig(string $provider, int $integrationId, ?string $usage = null): array
    {
        if (!self::_getProviderDefinition($provider, false, $usage)) {
            return [
                'error' => Craft::t('formie', 'Unknown integration option provider.'),
            ];
        }

        $integration = Formie::$plugin->getIntegrations()->getIntegrationById($integrationId);

        if (!$integration instanceof IntegrationInterface || !self::_integrationSupportsProvider($integration, $provider, $usage)) {
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
            'integrationOptions' => self::getEnabledIntegrationInstanceOptions($usage),
            'providerOptions' => self::getProviderOptionsForIntegration($integrationId, $usage),
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

    public static function providerSupportsUsage(string $provider, string $usage, bool $enabledOnly = false): bool
    {
        return self::_getProviderDefinition($provider, $enabledOnly, $usage) !== null
            || self::_getRegisteredProviderDefinition($provider, $usage) !== null;
    }

    private static function _getProviderDefinition(string $provider, bool $enabledOnly = false, ?string $usage = null): ?array
    {
        foreach (self::_getConfiguredSourceDefinitions($enabledOnly, $usage) as $definition) {
            if ($definition['handle'] === $provider) {
                return $definition;
            }
        }

        return null;
    }

    private static function _getRegisteredProviderDefinition(string $provider, ?string $usage = null): ?array
    {
        foreach (self::_getRegisteredSourceDefinitions($usage) as $definition) {
            if ($definition['handle'] === $provider) {
                return $definition;
            }
        }

        return null;
    }

    private static function _integrationSupportsProvider(IntegrationInterface $integration, string $provider, ?string $usage = null): bool
    {
        foreach (self::_getIntegrationSourceDefinitions($integration, $usage) as $definition) {
            if ($definition['handle'] === $provider) {
                return true;
            }
        }

        return false;
    }

    private static function _getConfiguredSourceDefinitions(bool $enabledOnly = false, ?string $usage = null): array
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

            foreach (self::_getIntegrationSourceDefinitions($integration, $usage) as $definition) {
                $definitions[] = [
                    ...$definition,
                    'integration' => $integration,
                    'integrationClass' => get_class($integration),
                ];
            }
        }

        return $definitions;
    }

    private static function _getRegisteredSourceDefinitions(?string $usage = null): array
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
                    if (!self::_definitionSupportsUsage($definition, $usage)) {
                        continue;
                    }

                    $definitions[] = [
                        ...$definition,
                        'integrationClass' => $integrationClass,
                    ];
                }
            }
        }

        return $definitions;
    }

    private static function _getIntegrationSourceDefinitions(IntegrationInterface $integration, ?string $usage = null): array
    {
        if (!method_exists($integration, 'getOptionSourceDefinitions')) {
            return [];
        }

        $integrationClass = get_class($integration);

        return array_values(array_filter(
            $integrationClass::getOptionSourceDefinitions(),
            static fn(array $definition): bool => self::_definitionSupportsUsage($definition, $usage),
        ));
    }

    private static function _definitionSupportsUsage(array $definition, ?string $usage): bool
    {
        if ($usage === null || $usage === self::USAGE_OPTIONS) {
            return true;
        }

        $usages = (array)($definition['optionSourceUsages'] ?? [self::USAGE_OPTIONS]);

        return in_array($usage, $usages, true);
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
