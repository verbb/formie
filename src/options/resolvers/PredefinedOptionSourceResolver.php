<?php
namespace verbb\formie\options\resolvers;

use verbb\formie\base\PredefinedOption;
use verbb\formie\Formie;
use verbb\formie\models\OptionSource;
use verbb\formie\options\OptionList;
use verbb\formie\options\OptionSourceContext;
use verbb\formie\options\OptionSourceFieldInterface;
use verbb\formie\options\OptionSourceResolverInterface;
use verbb\formie\options\OptionSourceValidationMode;

use verbb\formie\helpers\StringHelper;

class PredefinedOptionSourceResolver implements OptionSourceResolverInterface
{
    // Public Methods
    // =========================================================================

    public function supports(OptionSource $source): bool
    {
        return $source->type === 'predefined' && $source->provider;
    }

    public function resolve(OptionSourceFieldInterface $field, OptionSourceContext $context): OptionList
    {
        $source = $field->getOptionSource();

        if (!$source) {
            return OptionList::error('Missing option source configuration.');
        }

        $provider = $this->_getProviderClass($source);

        if (!$provider) {
            return OptionList::error('Unknown predefined option resolver.');
        }

        $labelKey = (string)($source->params['labelKey'] ?? $provider::$defaultLabelOption ?? 'name');
        $valueKey = (string)($source->params['valueKey'] ?? $provider::$defaultValueOption ?? 'name');

        $items = [];

        foreach ($provider::getDataOptions() as $row) {
            if (is_array($row)) {
                $label = (string)($row[$labelKey] ?? '');
                $value = (string)($row[$valueKey] ?? '');
            } else {
                $label = $value = (string)$row;
            }

            if ($label === '' && $value === '') {
                continue;
            }

            $items[] = [
                'label' => $label !== '' ? $label : $value,
                'value' => $value !== '' ? $value : $label,
            ];
        }

        return OptionList::fromRows($items);
    }

    public function validationMode(OptionSource $source): string
    {
        return OptionSourceValidationMode::STRICT;
    }


    // Private Methods
    // =========================================================================

    private function _getProviderClass(?OptionSource $source): ?PredefinedOption
    {
        if (!$source?->provider) {
            return null;
        }

        $providerId = StringHelper::toKebabCase($source->provider);

        foreach (Formie::$plugin->getOptionSources()->getRegisteredPredefinedOptions() as $option) {
            if ((string)$option === $providerId) {
                return $option;
            }
        }

        return null;
    }
}
