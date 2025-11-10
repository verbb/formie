<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\Formie;
use verbb\formie\base\SubFieldInnerFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyAddressCountryOptionsEvent;
use verbb\formie\fields\Dropdown;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;

class AddressCountry extends Dropdown implements SubFieldInnerFieldInterface
{
    // Constants
    // =========================================================================

    // Deprecated - remove at next breakpoint
    public const EVENT_MODIFY_COUNTRY_OPTIONS = 'modifyCountryOptions';


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
        return Formie::$plugin->getCountries()->getAddressCountries($this);
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

    public function getEmailHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool
    {
        return $this->_getValueLabel($value);
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
                    $this->getCountryOptions()
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

    protected function defineValueForVariable(mixed $value, Submission $submission, Notification $notification): mixed
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
