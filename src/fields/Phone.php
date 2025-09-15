<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\SubFieldInterface;
use verbb\formie\base\Field;
use verbb\formie\elements\Submission;
use verbb\formie\gql\types\generators\CountryOptionGenerator;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\Phone as PhoneModel;

use Craft;
use craft\base\ElementInterface;
use craft\base\InlineEditableFieldInterface;
use craft\base\PreviewableFieldInterface;
use craft\base\SortableFieldInterface;
use craft\helpers\Html;
use craft\helpers\Json;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

use Faker\Generator as FakerFactory;

use GraphQL\Type\Definition\Type;

use yii\base\Event;
use yii\db\Schema;

class Phone extends Field implements InlineEditableFieldInterface, PreviewableFieldInterface, SortableFieldInterface
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

    public static function getFieldSelectOptions(): array
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


    // Properties
    // =========================================================================

    public bool $countryEnabled = true;
    public ?string $countryDefaultValue = null;
    public string $countryLanguage = 'auto';
    public array $countryAllowed = [];


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

    public function hasSubFields(): bool
    {
        if ($this->countryEnabled) {
            return true;
        }

        return false;
    }

    public function getErrorKey(): string
    {
        // Ensure that we use the proper sub-field for validation errors
        return parent::getErrorKey() . '.number';
    }

    public function modifyAttributeLabels(array &$labels): void
    {
        // Because Phone fields aren't technically sub-fields, but they act like one with nested
        // field content, we want to ensure field validation picks up the correct field label
        $labels[$this->fieldKey . '.number'] = $this->label;
    }

    public function isValueEmpty(mixed $value, ?ElementInterface $element): bool
    {
        if ($value instanceof PhoneModel) {
            return $value->isEmpty();
        }

        return parent::isValueEmpty($value, $element);
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        $value = Json::decodeIfJson($value);

        if ($value instanceof PhoneModel) {
            $phone = $value;
        } else if (is_array($value)) {
            $phone = new PhoneModel($value);
            $phone->hasCountryCode = isset($value['country']);
        } else {
            $phone = new PhoneModel();
            $phone->number = $value;
            $phone->hasCountryCode = false;
        }

        return parent::normalizeValue($phone, $element);
    }

    public function getFrontEndJsModules(): ?array
    {
        if ($this->countryEnabled) {
            return [
                'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/fields/phone-country.js'),
                'module' => 'FormiePhoneCountry',
                'settings' => [
                    'countryDefaultValue' => $this->countryDefaultValue,
                    'countryAllowed' => $this->countryAllowed,
                    'language' => $this->_getMatchedLanguageId() ?? 'en',
                ],
            ];
        }

        return null;
    }

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/phone/preview', [
            'field' => $this,
        ]);
    }

    public function getCountryOptions(): array
    {
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
                'help' => Craft::t('formie', 'Set a default value for the field when it doesn’t have a value.'),
                'name' => 'defaultValue',
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Country Enabled'),
                'help' => Craft::t('formie', 'Whether to show the dial code on the country dropdown.'),
                'name' => 'countryEnabled',
            ]),
            SchemaHelper::multiSelectField([
                'label' => Craft::t('formie', 'Allowed Countries'),
                'help' => Craft::t('formie', 'Select which countries should be available to pick from. By default, all countries are available.'),
                'name' => 'countryAllowed',
                'if' => '$get(countryEnabled).value',
                'placeholder' => Craft::t('formie', 'Select an option'),
                'options' => $this->getCountryOptions(),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Country Default Value'),
                'help' => Craft::t('formie', 'Set a default value for the field when it doesn’t have a value.'),
                'name' => 'countryDefaultValue',
                'if' => '$get(countryEnabled).value',
                'options' => array_merge(
                    [['label' => Craft::t('formie', 'Select an option'), 'value' => '']],
                    $this->getCountryOptions()
                ),
            ]),
            // TODO: https://github.com/verbb/formie/issues/2042
            // SchemaHelper::selectField([
            //     'label' => Craft::t('formie', 'Language'),
            //     'help' => Craft::t('formie', 'Choose a specific language for countries to be translated with. Choose "Auto" for Formie to automatically match your site’s language.'),
            //     'name' => 'countryLanguage',
            //     'if' => '$get(countryEnabled).value',
            //     'options' => array_merge(
            //         static::getCountryLanguageOptions()
            //     ),
            // ]),
        ];
    }

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
            SchemaHelper::includeInEmailField(),
        ];
    }

    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
        ];
    }

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

            return new HtmlTag('input', [
                'type' => 'tel',
                'id' => $id,
                'class' => [
                    'fui-input',
                    $errors ? 'fui-error' : false,
                ],
                'name' => $this->getHtmlName('number'),
                'placeholder' => Craft::t('formie', $this->placeholder) ?: null,
                'autocomplete' => 'tel',
                'required' => $this->required ? true : null,
                'data' => [
                    'fui-id' => $dataId,
                    'required-message' => Craft::t('formie', $this->errorMessage) ?: null,
                ],
                'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
            ], $this->getInputAttributes());
        }

        if ($key === 'fieldCountryInput') {
            return new HtmlTag('input', [
                'type' => 'hidden',
                'id' => $this->getHtmlId($form, 'country'),
                'name' => $this->getHtmlName('country'),
                'data' => [
                    'fui-id' => $this->getHtmlDataId($form, 'country'),
                    'country' => true,
                ],
            ], $this->getInputAttributes());
        }
        
        return parent::defineHtmlTag($key, $context);
    }


    // Protected Methods
    // =========================================================================

    protected function cpInputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
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

            return new PhoneModel([
                'number' => $number,
                'country' => $phoneUtil->getRegionCodeForNumber($numberProto),
            ]);
        }

        return $faker->phoneNumber;
    }


    // Private Methods
    // =========================================================================

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
