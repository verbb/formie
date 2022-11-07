<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\Formie;
use verbb\formie\base\SubfieldInterface;
use verbb\formie\base\SubfieldTrait;
use verbb\formie\base\FormField;
use verbb\formie\gql\types\generators\FieldAttributeGenerator;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\Phone as PhoneModel;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use GraphQL\Type\Definition\Type;

use yii\base\Event;
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
    public static function getCountryOptions(): array
    {
        return Formie::$plugin->getPhone()->getCountries();
    }


    // Properties
    // =========================================================================

    public bool $countryEnabled = true;
    public bool $countryCollapsed = true;
    public bool $countryShowDialCode = true;
    public ?string $countryDefaultValue = null;
    public array $countryAllowed = [];


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getContentColumnType(): array|string
    {
        if ($this->countryEnabled) {
            return Schema::TYPE_TEXT;
        }

        return Schema::TYPE_STRING;
    }

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
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        $value = parent::normalizeValue($value, $element);
        $value = Json::decodeIfJson($value);

        if ($value instanceof PhoneModel) {
            return $value;
        }

        if (is_array($value)) {
            $phone = new PhoneModel($value);
            $phone->hasCountryCode = isset($value['country']);

            return $phone;
        }

        $phone = new PhoneModel();
        $phone->number = $value;
        $phone->hasCountryCode = false;

        return $phone;
    }

    /**
     * @inheritDoc
     */
    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if ($value instanceof PhoneModel) {
            $value = Json::encode($value);
        }

        return parent::serializeValue($value, $element);
    }

    public function getFrontEndJsModules(): ?array
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

        return null;
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
            'countryAllowed' => [],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndSubfields($context): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getSubfieldOptions(): array
    {
        return [
            [
                'label' => Craft::t('formie', 'Country (ISO)'),
                'handle' => 'country',
            ],
            [
                'label' => Craft::t('formie', 'Country (Full)'),
                'handle' => 'countryName',
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
    public function validateRequiredFields(ElementInterface $element): void
    {
        if ($this->required) {
            $value = $element->getFieldValue($this->handle);

            if (StringHelper::isBlank((string)$value->number)) {
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
            'countries' => static::getCountryOptions(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
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
            'field' => $this,
        ]);
    }

    public function populateValue($value): void
    {
        $this->defaultValue = $this->normalizeValue($value);
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'countryOptions' => [
                'name' => 'countryOptions',
                'type' => Type::listOf(FieldAttributeGenerator::generateType()),
            ],
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
                    'options' => static::getCountryOptions(),
                ]),
                SchemaHelper::selectField([
                    'label' => Craft::t('formie', 'Country Default Value'),
                    'help' => Craft::t('formie', 'Entering a default value will place the value in the field when it loads.'),
                    'name' => 'countryDefaultValue',
                    'options' => array_merge(
                        [['label' => Craft::t('formie', 'Select an option'), 'value' => '']],
                        static::getCountryOptions()
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
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Error Message'),
                'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                'name' => 'errorMessage',
                'if' => '$get(required).value',
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
            SchemaHelper::visibility(),
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
            SchemaHelper::enableContentEncryptionField(),
        ];
    }

    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        $form = $context['form'] ?? null;
        $errors = $context['errors'] ?? null;

        if ($key === 'fieldInput') {
            $id = $this->getHtmlId($form, '');
            $dataId = $this->getHtmlDataId($form, 'number');

            return new HtmlTag('input', array_merge([
                'type' => 'tel',
                'id' => $id,
                'class' => [
                    'fui-input',
                    $errors ? 'fui-error' : false,
                ],
                'name' => $this->getHtmlName('number'),
                'placeholder' => Craft::t('formie', $this->placeholder) ?: null,
                'autocomplete' => 'tel-national',
                'required' => $this->required ? true : null,
                'data' => [
                    'fui-id' => $dataId,
                    'fui-message' => Craft::t('formie', $this->errorMessage) ?: null,
                ],
                'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
            ], $this->getInputAttributes()));
        }

        if ($key === 'fieldCountryInput') {
            return new HtmlTag('input', array_merge([
                'type' => 'hidden',
                'id' => $this->getHtmlId($form, 'country'),
                'name' => $this->getHtmlName('country'),
                'data' => [
                    'fui-id' => $this->getHtmlDataId($form, 'country'),
                    'country' => true,
                ],
            ], $this->getInputAttributes()));
        }
        
        return parent::defineHtmlTag($key, $context);
    }
}
