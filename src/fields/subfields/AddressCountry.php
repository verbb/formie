<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\base\ChildFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Address;
use verbb\formie\fields\Dropdown;
use verbb\formie\Formie;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\Html;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\SlotTag;
use verbb\formie\models\Notification;
use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;

class AddressCountry extends Dropdown implements ChildFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Address - Country');
    }

    public static function getInputTemplatePath(): string
    {
        return 'fields/dropdown';
    }

    public static function getReferenceBlockTemplatePath(): string
    {
        return 'fields/dropdown';
    }


    // Properties
    // =========================================================================

    public ?string $optionLabel = 'full';
    public ?string $optionValue = 'short';


    // Public Methods
    // =========================================================================

    public function getFormBuilderSettings(): array
    {
        $settings = parent::getFormBuilderSettings();

        // Ensure the form builder knows about our dynamically-generated options
        $settings['options'] = $this->options();

        return $settings;
    }

    public function getCountryOptions(): array
    {
        $parent = $this->getParentField();
        $field = $parent instanceof Address ? $parent : null;
        $countries = Formie::$plugin->getCountries()->getAddressCountries($field);

        if ($field?->countryAllowed) {
            $allowed = array_map('strtoupper', $field->countryAllowed);
            $countries = array_values(array_filter(
                $countries,
                fn(array $country) => in_array(strtoupper($country['value']), $allowed, true),
            ));
        }

        return $countries;
    }

    public function options(): array
    {
        $options = [];

        foreach ($this->getCountryOptions() as $country) {
            $label = ($this->optionLabel === 'short') ? $country['value'] : $country['label'];
            $value = ($this->optionValue === 'short') ? $country['value'] : $country['label'];

            $options[] = ['label' => $label, 'value' => $value];
        }

        return $options;
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::comboboxField([
                'label' => Craft::t('formie', 'Default Value'),
                'instructions' => Craft::t('formie', 'Set a default value for the field when it doesn’t have a value.'),
                'name' => 'defaultValue',
                'placeholder' => Craft::t('formie', 'Select an option'),
                'options' => $this->options(),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Option Label'),
                'instructions' => Craft::t('formie', 'Select the format for the dropdown option label.'),
                'name' => 'optionLabel',
                'options' => [
                    ['label' => Craft::t('formie', 'Full Country Name (e.g. United States)'), 'value' => 'full'],
                    ['label' => Craft::t('formie', 'Abbreviated Country Name (e.g. US)'), 'value' => 'short'],
                ],
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Option Value'),
                'instructions' => Craft::t('formie', 'Select the format for the dropdown option value.'),
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

    protected function defineValueAsArray(mixed $value, ElementInterface $element = null): mixed
    {
        $label = $this->_getValueLabel($value);

        return $label !== '' ? [$label] : [];
    }

    protected function defineValueForExport(mixed $value, ElementInterface $element = null): mixed
    {
        return $this->_getValueLabel($value);
    }

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): string
    {
        return $this->_getValueLabel($value);
    }

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $tag = parent::defineFieldSlotTag($key, $context);

        if ($tag && $key === 'fieldInput') {
            $tag->mergeCoreAttributes([
                'autocomplete' => 'country',
                'data-formie-address-country-input' => true,
            ]);
        }

        return $tag;
    }


    // Private Methods
    // =========================================================================

    private function _getValueLabel(mixed $value): string
    {
        if (!$value) {
            return '';
        }

        // Match stored value against the same option list used by the front-end / CP (`optionValue` /
        // `optionLabel` settings). `getCountryOptions()` always uses ISO codes as `value`, so lookups
        // failed for exports when `optionValue` is "full" (stored value is e.g. "Zambia", not "ZM").
        if ($option = ArrayHelper::firstWhere($this->options(), 'value', $value)) {
            return $option['label'] ?? '';
        }

        // Fallback: value may be an ISO code from older data or integrations
        if ($countryOption = ArrayHelper::firstWhere($this->getCountryOptions(), 'value', $value)) {
            return $countryOption['label'] ?? '';
        }

        // Fallback: stored full name when only ISO-keyed list is available
        if ($countryOption = ArrayHelper::firstWhere($this->getCountryOptions(), 'label', $value)) {
            return $countryOption['label'] ?? '';
        }

        return '';
    }
}
