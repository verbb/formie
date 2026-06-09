<?php
namespace verbb\formie\options;

use verbb\formie\Formie;
use verbb\formie\base\ElementField;
use verbb\formie\helpers\StringHelper;

use Craft;

use Throwable;

class ElementOptionSourceHelper
{
    // Public Methods
    // =========================================================================

    public static function getProviderOptions(): array
    {
        $options = [];

        foreach (self::_getProviderDefinitions() as $definition) {
            $options[] = [
                'label' => $definition['label'],
                'value' => $definition['handle'],
            ];
        }

        return $options;
    }

    public static function getProviderFieldClass(string $provider): ?string
    {
        $provider = StringHelper::toKebabCase($provider);
        $definition = self::_getProviderDefinition($provider);

        return $definition['fieldClass'] ?? null;
    }

    public static function getProviderForFieldClass(string $fieldClass): ?string
    {
        foreach (self::_getProviderDefinitions() as $definition) {
            $providerFieldClass = $definition['fieldClass'];

            if ($fieldClass === $providerFieldClass || is_subclass_of($fieldClass, $providerFieldClass)) {
                return $definition['handle'];
            }
        }

        return null;
    }

    public static function buildParamsFromElementField(ElementField $field): array
    {
        return $field->getOptionSourceParams();
    }

    public static function resolveFromElementField(ElementField $field): OptionList
    {
        try {
            return OptionList::fromRows($field->getResolvedOptions());
        } catch (Throwable $e) {
            Craft::error('Element field failed to resolve options: ' . $e->getMessage(), __METHOD__);

            return OptionList::error(Craft::t('formie', 'Unable to resolve element options.'));
        }
    }

    public static function createProxyField(string $provider, array $params = []): ?ElementField
    {
        $class = self::getProviderFieldClass($provider);

        if (!$class) {
            return null;
        }

        /** @var ElementField $prototype */
        $prototype = new $class();

        /** @var ElementField $field */
        $field = new $class($prototype->getOptionSourceFieldConfig($params));

        return $field;
    }

    public static function buildFieldConfig(string $provider, array $params = []): array
    {
        $class = self::getProviderFieldClass($provider);

        if (!$class) {
            return [];
        }

        /** @var ElementField $field */
        $field = new $class();

        return $field->getOptionSourceFieldConfig($params);
    }

    public static function getBuilderConfig(string $provider): array
    {
        $provider = StringHelper::toKebabCase($provider);
        $fieldClass = self::getProviderFieldClass($provider);

        if (!$fieldClass) {
            return [
                'error' => Craft::t('formie', 'Unknown element provider.'),
            ];
        }

        /** @var ElementField $field */
        $field = new $fieldClass();
        $sourceOptions = $field->getOptionSourceSourceOptions();
        $multiSource = $field->getOptionSourceMode() === 'multiple';

        $defaultSource = $multiSource
            ? '*'
            : ($sourceOptions[0]['value'] ?? null);

        return [
            'provider' => $provider,
            'label' => $fieldClass::displayName(),
            'sourceMode' => $multiSource ? 'multiple' : 'single',
            'sourceOptions' => $sourceOptions,
            'labelSourceOptions' => $field->getLabelSourceOptions(),
            'orderByOptions' => $field->getOptionSourceOrderByOptions(),
            'defaults' => [
                'sources' => $multiSource ? '*' : null,
                'source' => $multiSource ? null : $defaultSource,
                'labelSource' => $field->labelSource,
                'orderBy' => $field->orderBy,
            ],
            'warning' => $field->getOptionSourceWarning($sourceOptions),
        ];
    }

    public static function resolveOptions(string $provider, array $params = []): OptionList
    {
        $field = self::createProxyField($provider, $params);

        if (!$field) {
            return OptionList::error(Craft::t('formie', 'Unknown element provider.'));
        }

        if ($error = self::_validateSourceSelection($field, $params)) {
            return OptionList::error($error);
        }

        try {
            return OptionList::fromRows($field->getResolvedOptions());
        } catch (Throwable $e) {
            Craft::error('Element option source failed to resolve: ' . $e->getMessage(), __METHOD__);

            return OptionList::error(Craft::t('formie', 'Unable to resolve element options.'));
        }
    }


    // Private Methods
    // =========================================================================

    private static function _validateSourceSelection(ElementField $field, array $params): ?string
    {
        if ($field->getOptionSourceMode() === 'multiple') {
            $sources = $params['sources'] ?? '*';

            if ($sources === '*' || (is_array($sources) && $sources !== [])) {
                return null;
            }

            return Craft::t('formie', 'Select at least one source.');
        }

        $source = $params['source'] ?? null;

        if (!$source && !empty($params['sources'])) {
            $sources = $params['sources'];
            $source = is_array($sources) ? ($sources[0] ?? null) : $sources;
        }

        if ($source) {
            return null;
        }

        return Craft::t('formie', 'Select a source.');
    }

    private static function _getProviderDefinition(string $provider): ?array
    {
        foreach (self::_getProviderDefinitions() as $definition) {
            if ($definition['handle'] === $provider) {
                return $definition;
            }
        }

        return null;
    }

    private static function _getProviderDefinitions(): array
    {
        $definitions = [];

        if (!Formie::$plugin) {
            return $definitions;
        }

        foreach (Formie::$plugin->getFields()->getRegisteredFieldTypes(false) as $fieldClass) {
            if (!is_subclass_of($fieldClass, ElementField::class)) {
                continue;
            }

            /** @var class-string<ElementField> $fieldClass */
            $definition = $fieldClass::getOptionSourceDefinition();

            if (!$definition) {
                continue;
            }

            $definitions[] = [
                ...$definition,
                'fieldClass' => $fieldClass,
            ];
        }

        return $definitions;
    }
}
