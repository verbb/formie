<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\base\SubFieldInnerFieldInterface;
use verbb\formie\fields\Dropdown;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;

use Craft;
use craft\base\ElementInterface;

use CommerceGuys\Addressing\Country\CountryRepository;

class AddressCountry extends Dropdown implements SubFieldInnerFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Address - Country');
    }

    public static function getFrontEndInputTemplatePath(): string
    {
        return 'fields/dropdown';
    }

    public static function getEmailTemplatePath(): string
    {
        return 'fields/dropdown';
    }

    public static function getCountryOptions(): array
    {
        $locale = Craft::$app->getLocale()->getLanguageID();

        $repo = new CountryRepository($locale);

        $countries = [];
        
        foreach ($repo->getList() as $value => $label) {
            $countries[] = compact('value', 'label');
        }

        return $countries;
    }


    // Properties
    // =========================================================================

    public ?string $optionLabel = 'full';
    public ?string $optionValue = 'short';


    // Public Methods
    // =========================================================================

    public function options(): array
    {
        foreach (static::getCountryOptions() as $country) {
            $label = ($this->optionLabel === 'short') ? $country['value'] : $country['label'];
            $value = ($this->optionValue === 'short') ? $country['value'] : $country['label'];

            $options[] = ['label' => $label, 'value' => $value];
        }

        return $options;
    }

    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Default Value'),
                'help' => Craft::t('formie', 'Set a default value for the field when it doesn’t have a value.'),
                'name' => 'defaultValue',
                'options' => array_merge(
                    [['label' => Craft::t('formie', 'Select an option'), 'value' => '']],
                    static::getCountryOptions()
                ),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Option Label'),
                'help' => Craft::t('formie', 'Select the format for the dropdown option label.'),
                'name' => 'optionLabel',
                'options' => [
                    ['label' => Craft::t('formie', 'Full Country Name (e.g. United States)'), 'value' => 'full'],
                    ['label' => Craft::t('formie', 'Abbreviated Country Name (e.g. US)'), 'value' => 'short'],
                ],
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Option Value'),
                'help' => Craft::t('formie', 'Select the format for the dropdown option value.'),
                'name' => 'optionValue',
                'options' => [
                    ['label' => Craft::t('formie', 'Full Country Name (e.g. United States)'), 'value' => 'full'],
                    ['label' => Craft::t('formie', 'Abbreviated Country Name (e.g. US)'), 'value' => 'short'],
                ],
            ]),
        ];
    }


    // Protected Methods
    // =========================================================================

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
    {
        return $this->_getValueLabel($value);
    }

    protected function defineValueAsJson(mixed $value, ElementInterface $element = null): string
    {
        return $this->_getValueLabel($value);
    }

    protected function defineValueForExport(mixed $value, ElementInterface $element = null): mixed
    {
        return $this->_getValueLabel($value);
    }

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): string
    {
        return $this->_getValueLabel($value);
    }


    // Private Methods
    // =========================================================================

    private function _getValueLabel(mixed $value): string
    {
        if ($value) {
            if ($countryOption = ArrayHelper::firstWhere($this->getCountryOptions(), 'value', $value)) {
                return $countryOption['label'] ?? '';
            }
        }

        return '';
    }
}
