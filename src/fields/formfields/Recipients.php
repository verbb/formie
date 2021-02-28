<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\Notification;
use verbb\formie\positions\Hidden;

use Craft;
use craft\base\ElementInterface;
use craft\fields\data\MultiOptionsFieldData;
use craft\fields\data\OptionData;
use craft\fields\data\SingleOptionFieldData;
use craft\helpers\Json;
use craft\helpers\StringHelper;

class Recipients extends FormField
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Recipients');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/recipients/icon.svg';
    }


    // Properties
    // =========================================================================

    public $displayType = 'hidden';
    public $options = [];
    public $multiple;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getIsFieldset(): bool
    {
        if ($this->displayType === 'checkboxes') {
            return true;
        }

        if ($this->displayType === 'radio') {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        // Sort out multiple options being set
        if (is_string($value) && Json::isJsonObject($value)) {
            $value = implode(',', array_filter(Json::decode($value)));
        }

        if (is_array($value)) {
            $value = implode(',', array_filter($value));
        }

        // Convert the placeholder values to their real values
        return $this->_getRealValue($value);
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/recipients/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'options' => $this->options,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/recipients/preview', [
            'field' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getEmailHtml(Submission $submission, Notification $notification, $value, array $options = null)
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function populateValue($value)
    {
        // Populate the default value correctly, when populating from Twig. We want to use placeholder values
        // instead of real values, so they aren't expose in the source
        $this->defaultValue = $this->_getFakeValue($value);
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        return [
            'labelPosition' => Hidden::class,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFieldOptions()
    {
        // Don't expose the value (email address) in the front end to prevent scaping
        $options = [];

        foreach ($this->options as $key => $value) {
            $options[$key] = $value;

            // Swap the value with the index
            $options[$key]['value'] = 'id:' . $key;
        }

        return $options;
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndInputOptions(Form $form, $value, array $options = null): array
    {
        $inputOptions = parent::getFrontEndInputOptions($form, $value, $options);

        $selectedValue = $inputOptions['value'] ?? '';

        // Special case for multi options
        if (strpos($selectedValue, ',') !== false) {
            $selectedValue = explode(',', $selectedValue);
        }

        // Make sure the front-end uses fake placeholder values
        $inputOptions['value'] = $this->_getFakeValue($selectedValue);

        return $inputOptions;
    }

    /**
     * @inheritDoc
     */
    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Display Type'),
                'help' => Craft::t('formie', 'Set different display layouts for this field.'),
                'name' => 'displayType',
                'options' => [
                    [ 'label' => Craft::t('formie', 'Hidden'), 'value' => 'hidden' ],
                    [ 'label' => Craft::t('formie', 'Dropdown'), 'value' => 'dropdown' ],
                    [ 'label' => Craft::t('formie', 'Checkboxes'), 'value' => 'checkboxes' ],
                    [ 'label' => Craft::t('formie', 'Radio Buttons'), 'value' => 'radio' ],
                ],
            ]),
            SchemaHelper::toggleContainer('!settings.displayType=hidden', [
                SchemaHelper::tableField([
                    'label' => Craft::t('formie', 'Options'),
                    'help' => Craft::t('formie', 'Define the available options for users to select from.'),
                    'name' => 'options',
                    'validation' => 'requiredIfNotEqual:displayType=hidden|uniqueLabels|requiredLabels',
                    'newRowDefaults' => [
                        'label' => '',
                        'value' => '',
                        'isDefault' => false,
                    ],
                    'columns' => [
                        [
                            'type' => 'label',
                            'label' => Craft::t('formie', 'Option Label'),
                            'class' => 'singleline-cell textual',
                        ],
                        [
                            'type' => 'value',
                            'label' => Craft::t('formie', 'Email'),
                            'class' => 'singleline-cell textual',
                        ],
                        [
                            'type' => 'default',
                            'label' => Craft::t('formie', 'Default?'),
                            'class' => 'thin checkbox-cell',
                        ],
                    ],
                ]),
            ]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineSettingsSchema(): array
    {
        return [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Required Field'),
                'help' => Craft::t('formie', 'Whether this field should be required when filling out the form.'),
                'name' => 'required',
            ]),
            SchemaHelper::toggleContainer('settings.required', [
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Error Message'),
                    'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                    'name' => 'errorMessage',
                ]),
            ]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
            SchemaHelper::inputAttributesField(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }


    // Private Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    private function _getRealValue($value)
    {
        // Check if we need to replace the value - for fields that define options in CP
        if (strpos($value, 'id:') !== false) {
            // Replace each occurance of the `id:X` placeholder value with their real value
            $value = preg_replace_callback('/id:(\d+)/m', function(array $match) use ($value): string {
                $index = $match[1] ?? 0;

                return $this->options[$index]['value'] ?? $value;
            }, $value);
        } 

        // For hidden fields, there's no CP defined options, so decode its encoded value
        if (strpos($value, 'base64:') !== false) {
            $value = StringHelper::decdec($value);

            // Check if this was an array of data
            if (is_string($value) && Json::isJsonObject($value)) {
                $value = implode(',', array_filter(Json::decode($value)));
            }
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    private function _getFakeValue($value)
    {
        if (in_array($this->displayType, ['dropdown', 'radio'])) {
            if (!($value instanceof SingleOptionFieldData)) {
                foreach ($this->options as $key => $option) {
                    $id =  'id:' . $key;

                    if ((string)$option['value'] === (string)$value) {
                        $value = new SingleOptionFieldData($option['label'], $id, true);

                        break;
                    }
                }
            }
        } else if ($this->displayType === 'checkboxes') {
            if (!($value instanceof MultiOptionsFieldData)) {
                $options = [];

                if (!is_array($value)) {
                    $value = [$value];
                }

                foreach ($this->options as $key => $option) {
                    $id =  'id:' . $key;

                    if (in_array((string)$option['value'], $value, true)) {
                        $options[] = new OptionData($option['label'], $id, true);
                    }
                }

                $value = new MultiOptionsFieldData($options);
            }
        } else if ($this->displayType === 'hidden') {
            // For a hidden field, there's no CP defined options, so encode the provided value
            // Also - support arrays of recipients in a hidden field
            if (is_array($value)) {
                $value = Json::encode($value);
            }

            $value = StringHelper::encenc($value);
        }

        return $value;
    }
}
