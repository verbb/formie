<?php
namespace verbb\formie\options;

use verbb\formie\Formie;
use verbb\formie\helpers\StringHelper;

use Craft;

use Throwable;

class OptionSourceProviderHelper
{
    // Constants
    // =========================================================================

    public const USAGE_OPTIONS = 'options';
    public const USAGE_RECIPIENTS = 'recipients';


    // Static Methods
    // =========================================================================

    public static function providerExists(string $provider, ?string $usage = null): bool
    {
        return self::_getProviderDefinition($provider, $usage) !== null;
    }

    public static function providerSupportsUsage(string $provider, string $usage): bool
    {
        return self::providerExists($provider, $usage);
    }

    public static function getProviderOptions(?string $usage = null): array
    {
        $options = [];

        foreach (self::_getProviderDefinitions($usage) as $definition) {
            $options[] = [
                'label' => $definition['label'],
                'value' => $definition['handle'],
            ];
        }

        return $options;
    }

    public static function getBuilderConfig(string $provider, array $params = [], ?string $usage = null): array
    {
        $provider = StringHelper::toKebabCase($provider);
        $definition = self::_getProviderDefinition($provider, $usage);

        if (!$definition) {
            return [
                'error' => Craft::t('formie', 'Unknown registered option source provider.'),
            ];
        }

        try {
            $config = $definition['instance']->getBuilderConfig($params);

            return [
                'provider' => $provider,
                'label' => $definition['label'],
                'paramFields' => array_values($config['paramFields'] ?? []),
                'defaults' => is_array($config['defaults'] ?? null) ? $config['defaults'] : [],
                'warning' => $config['warning'] ?? null,
            ];
        } catch (Throwable $e) {
            Craft::error('Registered option source failed to build config: ' . $e->getMessage(), __METHOD__);

            return [
                'error' => Craft::t('formie', 'Unable to load registered option source settings.'),
            ];
        }
    }

    public static function resolveOptions(string $provider, array $params = [], ?OptionSourceContext $context = null, ?string $usage = null): OptionList
    {
        $provider = StringHelper::toKebabCase($provider);
        $definition = self::_getProviderDefinition($provider, $usage);

        if (!$definition) {
            return OptionList::error(Craft::t('formie', 'Unknown registered option source provider.'));
        }

        $context ??= new OptionSourceContext(
            siteId: Craft::$app->getSites()->getCurrentSite()->id ?? null,
            scope: OptionSourceContext::SCOPE_RENDER,
        );

        try {
            $result = $definition['instance']->resolveOptions($params, $context);

            if ($result->error) {
                return $result;
            }

            $normalizedUsage = self::_resolveUsageForProvider($definition, $usage);
            $rows = self::_normalizeRows($result->items, $normalizedUsage);

            if ($normalizedUsage === self::USAGE_RECIPIENTS && $rows === [] && $result->items !== []) {
                return OptionList::error(Craft::t('formie', 'Registered recipient options must contain valid email addresses.'));
            }

            return OptionList::fromRows($rows, $result->error, $result->stale);
        } catch (Throwable $e) {
            Craft::error('Registered option source failed to resolve: ' . $e->getMessage(), __METHOD__);

            return OptionList::error(Craft::t('formie', 'Unable to resolve registered option source options.'));
        }
    }


    // Private Methods
    // =========================================================================

    private static function _normalizeRows(array $rows, ?string $usage): array
    {
        $normalized = [];

        foreach ($rows as $row) {
            if (!is_array($row) || isset($row['optgroup'])) {
                continue;
            }

            $label = trim((string)($row['label'] ?? ''));
            $value = trim((string)($row['value'] ?? ''));

            if ($label === '' && $value === '') {
                continue;
            }

            if ($usage === self::USAGE_RECIPIENTS && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $normalized[] = [
                'label' => $label !== '' ? $label : $value,
                'value' => $value !== '' ? $value : $label,
            ];
        }

        return $normalized;
    }

    private static function _resolveUsageForProvider(array $definition, ?string $usage): ?string
    {
        if ($usage !== null && in_array($usage, $definition['usages'], true)) {
            return $usage;
        }

        return $definition['usages'][0] ?? null;
    }

    private static function _getProviderDefinition(string $provider, ?string $usage = null): ?array
    {
        foreach (self::_getProviderDefinitions($usage) as $definition) {
            if ($definition['handle'] === $provider) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{handle:string,label:string,usages:string[],instance:OptionSourceProviderInterface,class:class-string<OptionSourceProviderInterface>}>
     */
    private static function _getProviderDefinitions(?string $usage = null): array
    {
        if (!Formie::$plugin) {
            return [];
        }

        $definitions = [];

        foreach (Formie::$plugin->getOptionSources()->getRegisteredProviderClasses() as $class) {
            if (!is_subclass_of($class, OptionSourceProviderInterface::class)) {
                continue;
            }

            $handle = StringHelper::toKebabCase($class::handle());

            if ($handle === '') {
                continue;
            }

            $usages = array_values(array_filter(array_map(
                static fn(mixed $value): string => trim((string)$value),
                $class::usages(),
            )));

            if ($usage !== null && $usage !== '' && !in_array($usage, $usages, true)) {
                continue;
            }

            $definitions[] = [
                'handle' => $handle,
                'label' => $class::displayName(),
                'usages' => $usages,
                'instance' => new $class(),
                'class' => $class,
            ];
        }

        return $definitions;
    }
}
