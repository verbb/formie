<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\Formie;
use verbb\formie\base\SubfieldInterface;
use verbb\formie\base\SubfieldTrait;
use verbb\formie\base\FormField;
use verbb\formie\elements\Form;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\Phone as PhoneModel;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use yii\db\Schema;

class Phone extends FormField implements SubfieldInterface, PreviewableFieldInterface
{
    // Traits
    // =========================================================================

    use SubfieldTrait;


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Phone Number');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/phone/icon.svg';
    }

    /**
     * Returns a list of countries and their extensions.
     *
     * @return array
     */
    public static function getCountries()
    {
        return Formie::$plugin->getPhone()->getCountries();
    }


    // Properties
    // =========================================================================

    public $countryEnabled = true;
    public $countryCollapsed = true;
    public $countryShowDialCode = true;
    public $countryDefaultValue;
    public $countryAllowed = [];


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getContentColumnType(): string
    {
        if ($this->countryEnabled) {
            return Schema::TYPE_TEXT;
        } else {
            return Schema::TYPE_STRING;
        }
    }

    /**
     * @inheritDoc
     */
    public function hasSubfields(): bool
    {
        if ($this->countryEnabled) {
            return true;
        }
        
        return false;
    }

    /**
     * @inheritDoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        $value = Json::decodeIfJson($value);

        if ($value instanceof PhoneModel) {
            return $value;
        } else if (is_array($value)) {
            $phone = new PhoneModel($value);
            $phone->hasCountryCode = isset($value['country']);

            return $phone;
        } else {
            $phone = new PhoneModel();
            $phone->number = $value;
            $phone->hasCountryCode = false;

            return $phone;
        }
    }

    /**
     * @inheritDoc
     */
    public function serializeValue($value, ElementInterface $element = null)
    {
        if ($value instanceof PhoneModel) {
            return Json::encode($value);
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function serializeValueForExport($value, ElementInterface $element = null)
    {
        return (string)$value;
    }

    /**
     * @inheritDoc
     */
    public function serializeValueForIntegration($value, ElementInterface $element = null)
    {
        if ($this->countryEnabled) {
            return $value->toArray();
        }

        return $value->number;
    }

    /**
     * @inheritdoc
     */
    public function getFrontEndJsModules()
    {
        if ($this->countryEnabled) {
            return [
                'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/fields/phone-country.js', true),
                'module' => 'FormiePhoneCountry',
                'settings' => [
                    'countryShowDialCode' => $this->countryShowDialCode,
                    'countryDefaultValue' => $this->countryDefaultValue,
                    'countryAllowed' => $this->countryAllowed,
                ],
            ];
        }
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        return [
            'countryEnabled' => true,
            'countryCollapsed' => true,
            'countryShowDialCode' => true,
            'countryDefaultValue' => '',
            'countryRestrict' => false,
            'countryAllowed' => [],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndSubfields(): array
    {
        $row = [];

        if ($this->countryEnabled) {
            $row['country'] = 'tel-country-code';
        }

        $row['number'] = 'tel-national';

        return [
            $row,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getSubfieldOptions(): array
    {
        return [
            [
                'label' => Craft::t('formie', 'Country'),
                'handle' => 'country',
            ],
            [
                'label' => Craft::t('formie', 'Country Code'),
                'handle' => 'countryCode',
            ],
            [
                'label' => Craft::t('formie', 'Number'),
                'handle' => 'number',
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function validateRequiredFields(ElementInterface $element)
    {
        if ($this->required) {
            $value = $element->getFieldValue($this->handle);

            if (StringHelper::isBlank($value->number)) {
                $element->addError(
                    $this->handle,
                    Craft::t('formie', '"{label}" cannot be blank.', [
                        'label' => $this->name,
                    ])
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getExtraBaseFieldConfig(): array
    {
        return [
            'countries' => static::getCountries(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/phone/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/phone/preview', [
            'field' => $this
        ]);
    }

    /**
     * @inheritDoc
     */
    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder'),
                'help' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                'name' => 'placeholder',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Default Value'),
                'help' => Craft::t('formie', 'Entering a default value will place the value in the field when it loads.'),
                'name' => 'defaultValue',
            ]),
            SchemaHelper::toggleBlock([
                'blockLabel' => Craft::t('formie', 'Country Code Dropdown'),
                'blockHandle' => 'country',
            ], [
                SchemaHelper::lightswitchField([
                    'label' => Craft::t('formie', 'Show Country Dial Code'),
                    'help' => Craft::t('formie', 'Whether to show the dial code on the country dropdown.'),
                    'name' => 'countryShowDialCode',
                ]),
                SchemaHelper::multiSelectField([
                    'label' => Craft::t('formie', 'Allowed Countries'),
                    'help' => Craft::t('formie', 'Select which countries should be available to pick from. By default, all countries are available.'),
                    'name' => 'countryAllowed',
                    'placeholder' => Craft::t('formie', 'Select an option'),
                    'options' => static::getCountries(),
                ]),
                SchemaHelper::selectField([
                    'label' => Craft::t('formie', 'Country Default Value'),
                    'help' => Craft::t('formie', 'Entering a default value will place the value in the field when it loads.'),
                    'name' => 'countryDefaultValue',
                    'options' => array_merge(
                        [[ 'label' => Craft::t('formie', 'Select an option'), 'value' => '' ]],
                        static::getCountries()
                    ),
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
            SchemaHelper::prePopulate(),
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
}
