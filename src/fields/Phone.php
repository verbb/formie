<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\FixedParentFieldInterface;
use verbb\formie\base\Field;
use verbb\formie\base\PreviewableFieldInterface;
use verbb\formie\base\SortableFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\fields\definitions\FieldClientModules;
use verbb\formie\fields\definitions\FieldReferenceValue;
use verbb\formie\fields\definitions\FieldValueClass;
use verbb\formie\gql\types\generators\CountryOptionGenerator;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\FieldBuilderPolicy;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\ValidationMessagesHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\fields\values\PhoneFieldValue;
use verbb\formie\models\ClientModule;
use verbb\formie\models\SlotTag;

use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Html;
use craft\helpers\Json;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

use Faker\Generator as FakerFactory;

use GraphQL\Type\Definition\Type;

use yii\base\Event;
use yii\db\Schema;

class Phone extends Field implements SortableFieldInterface, PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Phone Number');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/phone/icon.svg';
    }

    public static function getCountryLanguageOptions(): array
    {
        // See support https://github.com/jackocnr/intl-tel-input/tree/master/build/js/i18n
        $languages = [
            'Auto' => 'auto',
            'Arabic' => 'ar',
            'Bengali' => 'bn',
            'Bosnian' => 'bs',
            'Bulgarian' => 'bg',
            'Catalan' => 'ca',
            'Chinese' => 'zh',
            'Croatian' => 'hr',
            'Czech' => 'cs',
            'Dutch' => 'nl',
            'English' => 'en',
            'Finnish' => 'fi',
            'French' => 'fr',
            'German' => 'de',
            'Greek' => 'el',
            'Hindi' => 'hi',
            'Hungarian' => 'hu',
            'Indonesian' => 'id',
            'Italian' => 'it',
            'Japanese' => 'ja',
            'Korean' => 'ko',
            'Marathi' => 'mr',
            'Persian' => 'fa',
            'Polish' => 'pl',
            'Portuguese' => 'pt',
            'Romanian' => 'ro',
            'Russian' => 'ru',
            'Slovak' => 'sk',
            'Spanish' => 'es',
            'Swedish' => 'sv',
            'Telugu' => 'te',
            'Thai' => 'th',
            'Turkish' => 'tr',
            'Urdu' => 'ur',
        ];

        $languageOptions = [];

        foreach ($languages as $languageName => $languageCode) {
            $languageOptions[] = [
                'label' => Craft::t('formie', $languageName),
                'value' => $languageCode,
            ];
        }

        return $languageOptions;
    }

    public static function dbType(): string
    {
        return Schema::TYPE_JSON;
    }

    // Properties
    // =========================================================================

    public bool $countryEnabled = true;
    public ?string $countryDefaultValue = null;
    public string $countryLanguage = 'auto';
    public array $countryAllowed = [];
    public bool $countryPreselectFromIp = false;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        unset(
            $config['subfieldLabelPosition'],
            $config['subFieldLabelPosition'],
            $config['countryCollapsed'],
            $config['countryShowDialCode'],
            $config['countryRestrict'],
        );

        parent::__construct($config);
    }

    public function fieldKind(): string
    {
        return self::KIND_PHONE;
    }

    public function themeConfigKey(): string
    {
        return 'phoneNumber';
    }

    public function getErrorKey(): string
    {
        // Deprecated: use errorKey().
        return $this->errorKey();
    }

    public function errorKey(): string
    {
        // Ensure that we use the proper sub-field for validation errors
        return parent::errorKey() . '.number';
    }

    public function modifyAttributeLabels(array &$labels): void
    {
        // Because Phone fields aren't technically sub-fields, but they act like one with nested
        // field content, we want to ensure field validation picks up the correct field label
        $labels[$this->valueKey() . '.number'] = $this->label;
    }

    public function isValueEmpty(mixed $value, ?ElementInterface $element): bool
    {
        if ($value instanceof PhoneFieldValue && !$value->number) {
            return true;
        }

        return parent::isValueEmpty($value, $element);
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        $value = parent::normalizeValue($value, $element);
        $value = Json::decodeIfJson($value);

        if ($value instanceof PhoneFieldValue) {
            return $value;
        }

        if (is_array($value)) {
            $phone = new PhoneFieldValue($value);
            $phone->hasCountryCode = isset($value['country']);

            return $phone;
        }

        $phone = new PhoneFieldValue();
        $phone->number = $value;
        $phone->hasCountryCode = false;

        return $phone;
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewPhone(),
        ];
    }

    public function getCountryOptions(): array
    {
        return Formie::$plugin->getCountries()->getPhoneCountries($this);
    }

    public function getAllowedCountries(): array
    {
        // Allow the field to override what countries
        if ($this->countryAllowed) {
            return $this->countryAllowed;
        }

        // Otherwise, fall back to server, in case events have modified available countries.
        return Formie::$plugin->getCountries()->getPhoneCountries($this);
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'countryOptions' => [
                'name' => 'countryOptions',
                'type' => Type::listOf(CountryOptionGenerator::generateType()),
            ],
            'countryEnabled' => [
                'name' => 'countryEnabled',
                'type' => Type::boolean(),
            ],
            'countryDefaultValue' => [
                'name' => 'countryDefaultValue',
                'type' => Type::string(),
            ],
            'countryAllowed' => [
                'name' => 'countryAllowed',
                'type' => Type::string(),
                'resolve' => function($field) {
                    return Json::encode($field->countryAllowed);
                },
            ],
        ]);
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder'),
                'instructions' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                'name' => 'placeholder',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Default Value'),
                'instructions' => Craft::t('formie', 'Set a default value for the field when it doesn’t have a value.'),
                'name' => 'defaultValue',
            ]),
            ...FieldBuilderPolicy::phoneCountrySelectorSchema(),
            SchemaHelper::comboboxField([
                'label' => Craft::t('formie', 'Allowed Countries'),
                'instructions' => Craft::t('formie', 'Select which countries should be available to pick from. By default, all countries are available.'),
                'name' => 'countryAllowed',
                'if' => 'countryEnabled',
                'placeholder' => Craft::t('formie', 'Select an option'),
                'options' => $this->getCountryOptions(),
                'multiple' => true,
            ]),
            SchemaHelper::comboboxField([
                'label' => Craft::t('formie', 'Country Default Value'),
                'instructions' => Craft::t('formie', 'Set a default value for the field when it doesn’t have a value.'),
                'name' => 'countryDefaultValue',
                'if' => 'countryEnabled',
                'placeholder' => Craft::t('formie', 'Select an option'),
                'options' => $this->getCountryOptions(),
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Preselect Country from IP'),
                'instructions' => Craft::t('formie', 'When enabled, the country will be pre-filled based on the visitor’s IP address, when no default value is set.'),
                'name' => 'countryPreselectFromIp',
                'if' => 'countryEnabled',
            ]),
            // TODO: https://github.com/verbb/formie/issues/2042
            // SchemaHelper::selectField([
            //     'label' => Craft::t('formie', 'Language'),
            //     'instructions' => Craft::t('formie', 'Choose a specific language for countries to be translated with. Choose "Auto" for Formie to automatically match your site’s language.'),
            //     'name' => 'countryLanguage',
            //     'if' => 'countryEnabled',
            //     'options' => array_merge(
            //         static::getCountryLanguageOptions()
            //     ),
            // ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::prePopulate(),
            SchemaHelper::includeInEmailFieldSummariesField(),
        ];
    }

    public function defineFormBuilderValidationSchema(): array
    {
        return [
            SchemaHelper::requiredField(),
            SchemaHelper::requiredValidationMessage(),
        ];
    }

    public function defineFormBuilderAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
            SchemaHelper::errorMessagePosition($this),
        ];
    }

    public function defineFormBuilderAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
            SchemaHelper::inputAttributesField(),
            SchemaHelper::enableContentEncryptionField(),
        ];
    }

    public function defineFormBuilderConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    // Protected Methods
    // =========================================================================

    protected function supportedDefaults(): array
    {
        return ['countryEnabled', 'countryDefaultValue'];
    }

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;
        $errors = $context->errors;

        if ($key === 'fieldInput') {
            $id = $this->getHtmlId($form, '');
            $dataId = $this->getHtmlDataId($form, 'number');

            return SlotTag::make('input')
                ->core(array_merge([
                    'type' => 'tel',
                    'id' => $id,
                    'name' => $this->getHtmlName('number'),
                    'placeholder' => Craft::t('formie', $this->placeholder) ?: null,
                    'autocomplete' => 'tel',
                    'required' => $this->required ? true : null,
                    'data-formie-input' => true,
                    'data-formie-phone-input' => true,
                    'data-formie-input-id' => $dataId,
                    'data-formie-input-type' => 'tel',
                    'data-formie-input-error-state' => $errors ? true : false,
                    'aria-describedby' => $this->hasInstructions() ? "{$id}-instructions" : null,
                ], ValidationMessagesHelper::requiredClientAttributes($this)))
                ->theme([
                    'class' => [
                        'formie-input',
                        'formie-phone-input',
                        $errors ? 'formie-input-error' : false,
                    ],
                ])
                ->instanceAttributes($this->getInputAttributes());
        }

        if ($key === 'fieldCountryInput') {
            return SlotTag::make('input')
                ->core([
                    'type' => 'hidden',
                    'id' => $this->getHtmlId($form, 'country'),
                    'name' => $this->getHtmlName('country'),
                    'data-formie-input' => true,
                    'data-formie-phone-country-input' => true,
                    'data-formie-input-id' => $this->getHtmlDataId($form, 'country'),
                    'data-formie-input-type' => 'hidden',
                    'data-formie-country' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-input',
                        'formie-phone-country-input',
                    ],
                ])
                ->instanceAttributes($this->getInputAttributes());
        }
        
        return parent::defineFieldSlotTag($key, $context);
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/phone/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'element' => $element,
        ]);
    }

    protected function defineValueForEmailPreview(FakerFactory $faker): mixed
    {
        if ($this->countryEnabled) {
            $number = $faker->e164PhoneNumber;

            $phoneUtil = PhoneNumberUtil::getInstance();
            $numberProto = $phoneUtil->parse($number);

            return new PhoneFieldValue([
                'number' => $number,
                'country' => $phoneUtil->getRegionCodeForNumber($numberProto),
            ]);
        }

        return $faker->phoneNumber;
    }


    // Private Methods
    // =========================================================================

    protected function defineClientInput(): array
    {
        return array_merge(parent::defineClientInput(), [
            'countryEnabled' => $this->countryEnabled,
            'countryDefaultValue' => $this->countryDefaultValue,
        ]);
    }

    protected function defineClientModules(): array
    {
        $modules = parent::defineClientModules();

        if ($this->countryEnabled) {
            $modules[] = new ClientModule([
                'id' => 'phone-country',
                'config' => [
                    'countryDefaultValue' => $this->countryDefaultValue,
                    'countryAllowed' => $this->countryAllowed,
                    'countryPreselectFromIp' => $this->countryPreselectFromIp,
                    'countryFromIpAction' => 'formie/address/country-from-ip',
                    'language' => $this->_getMatchedLanguageId() ?? 'en',
                ],
            ]);
        }

        return $modules;
    }

    protected function defineValueClass(): ?string
    {
        return PhoneFieldValue::class;
    }

    protected function defineReferenceValues(): array
    {
        return [
            FieldReferenceValue::default([
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'country',
                'label' => Craft::t('formie', 'Country (ISO)'),
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'countryName',
                'label' => Craft::t('formie', 'Country (Full)'),
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'countryCode',
                'label' => Craft::t('formie', 'Country Code'),
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'number',
                'label' => Craft::t('formie', 'Number'),
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
        ];
    }

    private function _getMatchedLanguageId()
    {
        if ($this->countryLanguage && $this->countryLanguage != 'auto') {
            return $this->countryLanguage;
        }

        $currentLanguageId = Craft::$app->getLocale()->getLanguageID();
        $allCraftLocales = Craft::$app->getI18n()->getAllLocales();
        $allCraftLanguageIds = ArrayHelper::getColumn($allCraftLocales, 'id');
        $allRecaptchaLanguageIds = ArrayHelper::getColumn(static::getCountryLanguageOptions(), 'value');
        $matchedLanguageIds = array_intersect($allRecaptchaLanguageIds, $allCraftLanguageIds);

        // If our current request Language ID matches a language ID, use it
        if (in_array($currentLanguageId, $matchedLanguageIds, true)) {
            return $currentLanguageId;
        }

        // If our current language ID has a more generic match, use it
        if (str_contains($currentLanguageId, '-')) {
            $parts = explode('-', $currentLanguageId);
            $baseLanguageId = $parts['0'] ?? null;

            if (in_array($baseLanguageId, $matchedLanguageIds, true)) {
                return $baseLanguageId;
            }
        }

        return null;
    }
}
