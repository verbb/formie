<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\Formie;
use verbb\formie\base\FormFieldTrait;
use verbb\formie\gql\types\generators\FieldOptionGenerator;
use verbb\formie\models\IntegrationField;

use Craft;
use craft\base\ElementInterface;
use craft\fields\BaseOptionsField as CraftBaseOptionsField;
use craft\fields\data\MultiOptionsFieldData;
use craft\fields\data\OptionData;
use craft\fields\data\SingleOptionFieldData;

use GraphQL\Type\Definition\Type;

use Throwable;

use LitEmoji\LitEmoji;

abstract class BaseOptionsField extends CraftBaseOptionsField
{
    // Traits
    // =========================================================================

    use FormFieldTrait {
        getSavedFieldConfig as traitGetSavedFieldConfig;
        getFrontEndInputOptions as traitGetFrontendInputOptions;
        getDefaultValue as traitGetDefaultValue;
        defineValueForIntegration as traitDefineValueForIntegration;
        getSettingGqlTypes as traitGetSettingGqlTypes;
    }


    // Properties
    // =========================================================================

    /**
     * @var bool
     */
    public bool $searchable = true;

    /**
     * @var string|null vertical or horizontal layout
     */
    public ?string $layout = null;

    /**
     * @var bool Whether this field should use multiple values. Note this only effects
     * the `name` attribute when rendering, forcing to use `handle[]` instead of `handle`.
     * This is currently only enforced by element fields which need array data.
     */
    public bool $hasMultiNamespace = false;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        parent::init();

        foreach ($this->options as &$option) {
            // Cleanup any values set in Vue
            unset($option['isNew']);
        }

        // Decode any emoji's in options
        $this->_normalizeOptions();
    }

    public function getSavedFieldConfig(): array
    {
        // Normalize options when generating the field config for the form builder.
        // Otherwise, the shortcodes will be shown after saving and refreshing the form builder.
        $this->_normalizeOptions();

        return $this->traitGetSavedFieldConfig();
    }

    /**
     * @inheritDoc
     */
    public function getValue(ElementInterface $element): mixed
    {
        $value = $element->getFieldValue($this->handle);

        if ($value instanceof SingleOptionFieldData) {
            return $value->value;
        }

        if ($value instanceof MultiOptionsFieldData) {
            $values = [];
            foreach ($value as $selectedValue) {
                /** @var OptionData $selectedValue */
                $values[] = $selectedValue->value;
            }

            return $values;
        }

        return null;
    }

    public function getDefaultValue()
    {
        $value = $this->traitGetDefaultValue() ?? $this->defaultValue;

        // If the default value from the parent field (query params, etc.) is empty, use the default values
        // set in the field option settings.
        if ($value === '') {
            $value = [];

            foreach ($this->options() as $option) {
                if (!empty($option['isDefault'])) {
                    $value[] = $option['value'];
                }
            }

            if (!$this->multi) {
                $value = $value[0] ?? '';
            }
        }

        try {
            $optionValues = [];
            $optionLabels = [];

            foreach ($this->options() as $option) {
                if (!isset($option['optgroup'])) {
                    $optionValues[] = (string)$option['value'];
                    $optionLabels[] = (string)$option['label'];
                }
            }

            if ($this->multi) {
                $selectedOptions = [];

                $selectedValues = !is_array($value) ? [$value] : $value;

                foreach ($selectedValues as $selectedValue) {
                    $index = array_search($selectedValue, $optionValues, true);
                    $valid = $index !== false;
                    $label = $valid ? $optionLabels[$index] : null;
                    $selectedOptions[] = new OptionData($label, $selectedValue, true, $valid);
                }

                return new MultiOptionsFieldData($selectedOptions);
            }

            $index = array_search($value, $optionValues, true);
            $valid = $index !== false;
            $label = $valid ? $optionLabels[$index] : null;

            return new SingleOptionFieldData($label, $value, true, $valid);
        } catch (Throwable $e) {
            Formie::error(Craft::t('formie', '{handle}: “{message}” {file}:{line}', [
                'handle' => $this->handle,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));
        }

        return $value;
    }

    /**
     * Validates the options.
     */
    public function validateOptions(): void
    {
        $labels = [];
        $values = [];
        $hasDuplicateLabels = false;
        $hasDuplicateValues = false;
        $optgroup = '__root__';

        foreach ($this->options as &$option) {
            // Ignore optgroups
            if (array_key_exists('optgroup', $option)) {
                $optgroup = $option['optgroup'];
                continue;
            }

            $label = (string)$option['label'];
            $value = (string)$option['value'];

            if (isset($labels[$optgroup][$label])) {
                $option['hasDuplicateLabels'] = true;
                $hasDuplicateLabels = true;
            }

            if (isset($values[$value])) {
                $option['hasDuplicateValues'] = true;
                $hasDuplicateValues = true;
            }
            $labels[$optgroup][$label] = $values[$value] = true;
        }

        unset($option);

        if ($hasDuplicateLabels) {
            $this->addError('options', Craft::t('app', 'All option labels must be unique.'));
        }
        if ($hasDuplicateValues) {
            $this->addError('options', Craft::t('app', 'All option values must be unique.'));
        }
    }

    /**
     * @inheritDoc
     */
    public function getSavedSettings(): array
    {
        return $this->getSettings();
    }

    /**
     * @inheritDoc
     */
    public function beforeSave(bool $isNew): bool
    {
        // Fix an error with migrating from Freeform/Sprout where the default value is set.
        // Can eventually remove this!
        $this->defaultValue = null;

        // Convert labels that contain emojis
        foreach ($this->options as &$option) {
            if (isset($option['label'])) {
                $option['label'] = LitEmoji::unicodeToShortcode($option['label']);
            }
        }

        return parent::beforeSave($isNew);
    }

    public function getSettingGqlTypes(): array
    {
        $types = array_merge($this->traitGetSettingGqlTypes(), [
            'options' => [
                'name' => 'options',
                'type' => Type::listOf(FieldOptionGenerator::generateType()),
            ],
        ]);

        // Remove this for dropdowns, which is a duplicate of `multi`
        unset($types['multiple'], $types['optgroups']);

        return $types;
    }


    // Protected Methods
    // =========================================================================

    protected function defineValueAsString($value, ElementInterface $element = null): string
    {
        if ($value instanceof MultiOptionsFieldData) {
            return implode(', ', array_map(function($item) {
                return $item->value;
            }, (array)$value));
        }

        return $value->value ?? '';
    }

    protected function defineValueForIntegration($value, $integrationField, $integration, ElementInterface $element = null, $fieldKey = ''): mixed
    {
        // If mapping to an array, extract just the values
        if ($integrationField->getType() === IntegrationField::TYPE_ARRAY) {
            if ($value instanceof MultiOptionsFieldData) {
                return array_map(function($item) {
                    return $item->value;
                }, (array)$value);
            }

            return [$value->value];
        }

        // Fetch the default handling
        return $this->traitDefineValueForIntegration($value, $integrationField, $integration, $element);
    }

    protected function defineValueForSummary($value, ElementInterface $element = null): string
    {
        if ($value instanceof MultiOptionsFieldData) {
            return implode(', ', array_map(function($item) {
                return $item->label;
            }, (array)$value));
        }

        return $value->label ?? '';
    }

    protected function getPredefinedOptions(): array
    {
        return Formie::$plugin->getPredefinedOptions()->getPredefinedOptions();
    }


    // Private Methods
    // =========================================================================

    private function _normalizeOptions()
    {
        foreach ($this->options as &$option) {
            // Decode any emoji's in options
            if (isset($option['label'])) {
                $option['label'] = LitEmoji::shortcodeToUnicode($option['label']);
                $option['label'] = trim(preg_replace('/\R/u', "\n", $option['label']));
            }
        }
    }
}
