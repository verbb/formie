<?php
namespace verbb\formie\services;

use verbb\formie\events\RegisterOptionSourceResolversEvent;
use verbb\formie\events\RegisterPredefinedOptionsEvent;
use verbb\formie\Formie;
use verbb\formie\helpers\OptionsMode;
use verbb\formie\models\OptionSource;
use verbb\formie\options\predefined;
use verbb\formie\options\OptionList;
use verbb\formie\options\OptionSourceContext;
use verbb\formie\options\OptionSourceFieldInterface;
use verbb\formie\options\OptionSourceResolverInterface;
use verbb\formie\options\OptionSourceValidationMode;
use verbb\formie\options\ElementOptionSourceHelper;
use verbb\formie\options\IntegrationOptionSourceHelper;
use verbb\formie\options\resolvers\PredefinedOptionSourceResolver;
use verbb\formie\options\resolvers\ElementOptionSourceResolver;
use verbb\formie\options\resolvers\IntegrationOptionSourceResolver;

use Craft;
use craft\base\Component;

class OptionSources extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_REGISTER_OPTION_SOURCE_RESOLVERS = 'registerOptionSourceResolvers';
    public const EVENT_REGISTER_PREDEFINED_OPTIONS = 'registerPredefinedOptions';


    // Properties
    // =========================================================================

    private array $_resolvers = [];
    private array $_cache = [];
    private array $_predefinedOptions = [];


    // Public Methods
    // =========================================================================

    public function getResolvers(): array
    {
        if ($this->_resolvers !== []) {
            return $this->_resolvers;
        }

        $resolvers = [
            new PredefinedOptionSourceResolver(),
            new ElementOptionSourceResolver(),
            new IntegrationOptionSourceResolver(),
        ];

        $event = new RegisterOptionSourceResolversEvent([
            'resolvers' => $resolvers,
        ]);

        $this->trigger(self::EVENT_REGISTER_OPTION_SOURCE_RESOLVERS, $event);

        $this->_resolvers = $event->resolvers;

        return $this->_resolvers;
    }

    public function getPredefinedProviderOptions(): array
    {
        $options = [];

        foreach ($this->getRegisteredPredefinedOptions() as $provider) {
            $options[] = [
                'label' => $provider::displayName(),
                'value' => (string)$provider,
            ];
        }

        return $options;
    }

    public function getRegisteredPredefinedOptions(): array
    {
        if ($this->_predefinedOptions !== []) {
            return $this->_predefinedOptions;
        }

        $options = [
            predefined\Countries::class,
            predefined\StatesAustralia::class,
            predefined\StatesCanada::class,
            predefined\StatesUsa::class,
            predefined\Continents::class,

            predefined\Days::class,
            predefined\Months::class,

            predefined\Currencies::class,
            predefined\Languages::class,

            predefined\Industry::class,
            predefined\Education::class,
            predefined\Employment::class,
            predefined\MaritalStatus::class,
            predefined\Age::class,
            predefined\Gender::class,
            predefined\Size::class,

            predefined\Acceptability::class,
            predefined\Agreement::class,
            predefined\LikertScale::class,
            predefined\StarRating::class,
            predefined\Comparison::class,
            predefined\Difficulty::class,
            predefined\HowLong::class,
            predefined\HowOften::class,
            predefined\Importance::class,
            predefined\Satisfaction::class,
            predefined\WouldYou::class,
        ];

        $event = new RegisterPredefinedOptionsEvent([
            'options' => $options,
        ]);

        $this->trigger(self::EVENT_REGISTER_PREDEFINED_OPTIONS, $event);

        foreach ($event->options as $class) {
            $this->_predefinedOptions[$class] = new $class;
        }

        return $this->_predefinedOptions;
    }

    public function getPredefinedOptions(): array
    {
        $options = [
            ['label' => Craft::t('formie', 'Select an option'), 'value' => ''],
        ];

        foreach ($this->getRegisteredPredefinedOptions() as $availableOption) {
            $options[] = [
                'label' => Craft::t('formie', $availableOption::displayName()),
                'value' => get_class($availableOption),
            ];
        }

        return $options;
    }

    public function getPredefinedOptionsForType($type): array
    {
        $option = null;

        foreach ($this->getRegisteredPredefinedOptions() as $registeredOption) {
            if (get_class($registeredOption) === $type || (string)$registeredOption === $type) {
                $option = $registeredOption;
                break;
            }
        }

        if (!$option) {
            return [];
        }

        return [
            'data' => $option::getDataOptions(),
            'labelOptions' => $option::getLabelOptions(),
            'valueOptions' => $option::getValueOptions(),
            'labelOption' => $option::$defaultLabelOption,
            'valueOption' => $option::$defaultValueOption,
        ];
    }

    public function getElementProviderOptions(): array
    {
        return ElementOptionSourceHelper::getProviderOptions();
    }

    public function getElementProviderBuilderConfig(string $provider): array
    {
        return ElementOptionSourceHelper::getBuilderConfig($provider);
    }

    public function hasIntegrationOptionSources(?string $usage = null): bool
    {
        return IntegrationOptionSourceHelper::hasEnabledIntegrationOptionSources($usage);
    }

    public function getIntegrationProviderOptions(?string $usage = null): array
    {
        return IntegrationOptionSourceHelper::getProviderOptions($usage);
    }

    public function getEnabledIntegrationOptions(?string $usage = null): array
    {
        return IntegrationOptionSourceHelper::getEnabledIntegrationInstanceOptions($usage);
    }

    public function getIntegrationOptions(string $provider, ?string $usage = null): array
    {
        return IntegrationOptionSourceHelper::getIntegrationOptions($provider, $usage);
    }

    public function getIntegrationBuilderConfigForIntegration(int $integrationId, ?string $usage = null): array
    {
        return IntegrationOptionSourceHelper::getBuilderConfigForIntegration($integrationId, $usage);
    }

    public function getIntegrationBuilderConfig(string $provider, int $integrationId, ?string $usage = null): array
    {
        return IntegrationOptionSourceHelper::getBuilderConfig($provider, $integrationId, $usage);
    }

    public function resolve(OptionSourceFieldInterface $field, ?OptionSourceContext $context = null): OptionList
    {
        $mode = $field->getOptionsMode();

        if ($mode !== OptionsMode::DYNAMIC) {
            return OptionList::fromRows($field->options());
        }

        $source = $field->getOptionSource();

        if (!$source) {
            return OptionList::fromRows($field->options());
        }

        $context ??= new OptionSourceContext(
            siteId: Craft::$app->getSites()->getCurrentSite()->id ?? null,
            scope: OptionSourceContext::SCOPE_RENDER,
        );

        $cacheKey = $this->_cacheKey($field, $source, $context);

        if (isset($this->_cache[$cacheKey])) {
            return $this->_cache[$cacheKey];
        }

        foreach ($this->getResolvers() as $provider) {
            if (!$provider instanceof OptionSourceResolverInterface || !$provider->supports($source)) {
                continue;
            }

            $result = $provider->resolve($field, $context);

            if ($result->error && $field->options) {
                $result = OptionList::fromRows($field->options(), $result->error, true);
            }

            $this->_cache[$cacheKey] = $result;

            return $result;
        }

        return OptionList::fromRows($field->options(), 'No option source resolver matched this configuration.');
    }

    public function getValidationMode(OptionSourceFieldInterface $field): string
    {
        if ($field->getOptionsMode() === OptionsMode::STATIC) {
            return OptionSourceValidationMode::STRICT;
        }

        if ($field->getOptionsMode() === OptionsMode::TEMPLATE) {
            return OptionSourceValidationMode::ACCEPT_SUBMITTED;
        }

        $source = $field->getOptionSource();

        if (!$source) {
            return OptionSourceValidationMode::STRICT;
        }

        foreach ($this->getResolvers() as $provider) {
            if (!$provider instanceof OptionSourceResolverInterface || !$provider->supports($source)) {
                continue;
            }

            return OptionSourceValidationMode::normalize($provider->validationMode($source));
        }

        return OptionSourceValidationMode::STRICT;
    }

    public function resolveRows(OptionSourceFieldInterface $field, ?OptionSourceContext $context = null): array
    {
        return $this->resolve($field, $context)->items;
    }

    public function detachToStatic(OptionSourceFieldInterface $field, ?OptionSourceContext $context = null): array
    {
        $context ??= new OptionSourceContext(scope: OptionSourceContext::SCOPE_DETACH);
        $rows = $this->resolveRows($field, $context);

        return array_values(array_filter($rows, static fn(array $row): bool => !isset($row['optgroup'])));
    }


    // Private Methods
    // =========================================================================

    private function _cacheKey(OptionSourceFieldInterface $field, OptionSource $source, OptionSourceContext $context): string
    {
        return md5(json_encode([
            $field->uid ?? $field->handle ?? spl_object_hash($field),
            $context->form?->id,
            $context->submission?->id,
            $context->ownerElement?->id,
            $context->siteId,
            $context->scope,
            $source->toConfig(),
        ]));
    }
}
