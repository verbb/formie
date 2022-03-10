<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\base\SubfieldInterface;
use verbb\formie\base\SubfieldTrait;
use verbb\formie\events\ModifyDateTimeFormatEvent;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\IntegrationField;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\gql\types\DateTime as DateTimeType;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\i18n\Locale;

use yii\base\Event;
use yii\db\Schema;

use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\Type;

use DateTime;

class Date extends FormField implements SubfieldInterface, PreviewableFieldInterface
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_DATE_FORMAT = 'modifyDateFormat';
    public const EVENT_MODIFY_TIME_FORMAT = 'modifyTimeFormat';


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
        return Craft::t('formie', 'Date/Time');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/date/icon.svg';
    }

    public static function toDateTime($value): DateTime|bool
    {
        // We should never deal with timezones
        return DateTimeHelper::toDateTime($value, false, false);
    }


    // Properties
    // =========================================================================

    public string $dateFormat = 'Y-m-d';
    public string $timeFormat = 'H:i';
    public string $displayType = 'calendar';
    public bool $includeDate = true;
    public bool $includeTime = false;
    public ?string $timeLabel = null;
    public ?string $defaultOption = null;
    public ?string $dayLabel = null;
    public ?string $dayPlaceholder = null;
    public ?string $monthLabel = null;
    public ?string $monthPlaceholder = null;
    public ?string $yearLabel = null;
    public ?string $yearPlaceholder = null;
    public ?string $hourLabel = null;
    public ?string $hourPlaceholder = null;
    public ?string $minuteLabel = null;
    public ?string $minutePlaceholder = null;
    public ?string $secondLabel = null;
    public ?string $secondPlaceholder = null;
    public ?string $ampmLabel = null;
    public ?string $ampmPlaceholder = null;
    public bool $useDatePicker = true;
    public array $datePickerOptions = [];
    public mixed $minDate = null;
    public mixed $maxDate = null;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        if ($this->defaultOption === 'date') {
            if ($this->defaultValue && !$this->defaultValue instanceof DateTime) {
                $defaultValue = self::toDateTime($this->defaultValue);

                if ($defaultValue) {
                    $this->defaultValue = $defaultValue;
                }
            }
        } else if ($this->defaultOption === 'today') {
            $this->defaultValue = self::toDateTime(new DateTime());
        } else {
            $this->defaultValue = '';
        }
    }

    /**
     * @inheritDoc
     */
    public function getContentColumnType(): array|string
    {
        if ($this->getIsTime()) {
            return Schema::TYPE_TIME;
        }

        return Schema::TYPE_DATETIME;
    }

    /**
     * @inheritDoc
     */
    public function hasSubfields(): bool
    {
        return $this->displayType !== 'calendar';
    }

    public function getDateFormat(): ?string
    {
        // Allow plugins to modify the date format, commonly for specific sites
        $event = new ModifyDateTimeFormatEvent([
            'field' => $this,
            'dateFormat' => $this->dateFormat,
        ]);

        Event::trigger(static::class, self::EVENT_MODIFY_DATE_FORMAT, $event);

        return $event->dateFormat;
    }

    public function getTimeFormat(): ?string
    {
        // Allow plugins to modify the date format, commonly for specific sites
        $event = new ModifyDateTimeFormatEvent([
            'field' => $this,
            'timeFormat' => $this->timeFormat,
        ]);

        Event::trigger(static::class, self::EVENT_MODIFY_DATE_FORMAT, $event);

        return $event->timeFormat;
    }

    /**
     * @inheritdoc
     */
    public function getTableAttributeHtml(mixed $value, ElementInterface $element): string
    {
        if ($value && $this->getIsDateTime()) {
            return Craft::$app->getFormatter()->asDatetime($value, Locale::LENGTH_SHORT);
        }

        if ($value && $this->getIsTime()) {
            return Craft::$app->getFormatter()->asTime($value, Locale::LENGTH_SHORT);
        }

        if ($value && $this->getIsDate()) {
            return Craft::$app->getFormatter()->asDate($value, Locale::LENGTH_SHORT);
        }

        return '';
    }

    /**
     * @inheritDoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if (!$value || $value instanceof DateTime) {
            return $value;
        }

        // For dropdowns and inputs, we need to convert our array syntax to string
        if ($this->displayType === 'dropdowns' || $this->displayType === 'inputs') {
            if (is_array($value)) {
                $value = array_filter($value);

                // Convert array-syntax value into a date string. Ensure we pad it out to fill in gaps.
                $dateTime['date'] = implode('-', [
                    StringHelper::padLeft(($value['Y'] ?? '0000'), 4, '0'),
                    StringHelper::padLeft(($value['m'] ?? '00'), 2, '0'),
                    StringHelper::padLeft(($value['d'] ?? '00'), 2, '0'),
                ]);

                $dateTime['time'] = implode(':', [
                    StringHelper::padLeft(($value['H'] ?? $value['h'] ?? '00'), 2, '0'),
                    StringHelper::padLeft(($value['i'] ?? '00'), 2, '0'),
                    StringHelper::padLeft(($value['s'] ?? '00'), 2, '0'),
                ]);

                // Strip out any invalid dates (time-only field) which will fail to save
                if ($dateTime['date'] === '0000-00-00') {
                    unset($dateTime['date']);
                }

                $value = $dateTime;
            }
        }

        if ($this->getIsTime()) {
            if (is_array($value)) {
                return self::toDateTime($value) ?: null;
            }

            return self::toDateTime(['time' => $value]) ?: null;
        }

        if (($date = self::toDateTime($value)) !== false) {
            return $date;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getSearchKeywords(mixed $value, ElementInterface $element): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        $settings = Formie::$plugin->getSettings();
        $displayType = $settings->defaultDateDisplayType ?? 'calendar';
        $defaultOption = $settings->defaultDateValue ?? '';
        $defaultValue = $settings->getDefaultDateTimeValue();

        return [
            'dateFormat' => 'Y-m-d',
            'timeFormat' => 'H:i',
            'displayType' => $displayType,
            'defaultValue' => $defaultValue,
            'defaultOption' => $defaultOption,
            'includeDate' => true,
            'includeTime' => true,
            'dayLabel' => Craft::t('formie', 'Day'),
            'dayPlaceholder' => '',
            'monthLabel' => Craft::t('formie', 'Month'),
            'monthPlaceholder' => '',
            'yearLabel' => Craft::t('formie', 'Year'),
            'yearPlaceholder' => '',
            'hourLabel' => Craft::t('formie', 'Hour'),
            'hourPlaceholder' => '',
            'minuteLabel' => Craft::t('formie', 'Minute'),
            'minutePlaceholder' => '',
            'secondLabel' => Craft::t('formie', 'Second'),
            'secondPlaceholder' => '',
            'ampmLabel' => Craft::t('formie', 'AM/PM'),
            'ampmPlaceholder' => '',
            'useDatePicker' => true,
            'datePickerOptions' => [],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndSubfields(): array
    {
        $defaultValue = $this->defaultValue ?: new DateTime();
        $year = (int)$defaultValue->format('Y');
        $minYear = $year - 100;
        $maxYear = $year + 100;

        $yearOptions = [];

        for ($y = $minYear; $y < $maxYear; $y++) {
            $yearOptions[] = ['value' => $y, 'label' => $y];
        }

        $available = [
            'Y' => [
                'handle' => 'year',
                'options' => $yearOptions,
                'min' => $minYear,
                'max' => $maxYear,
            ],
            'm' => [
                'handle' => 'month',
                'options' => $this->getMonths(),
                'min' => 1,
                'max' => 12,
            ],
            'd' => [
                'handle' => 'day',
                'min' => 1,
                'max' => 31,
            ],
            'H' => [
                'handle' => 'hour',
                'min' => 0,
                'max' => 23,
            ],
            'h' => [
                'handle' => 'hour',
                'min' => 1,
                'max' => 12,
            ],
            'i' => [
                'handle' => 'minute',
                'min' => 0,
                'max' => 59,
            ],
            's' => [
                'handle' => 'second',
                'min' => 0,
                'max' => 59,
            ],
            'A' => [
                'handle' => 'ampm',
                'options' => [
                    ['value' => 'AM', 'label' => 'AM'],
                    ['value' => 'PM', 'label' => 'PM'],
                ],
                'maxlength' => 2,
            ],
        ];

        $format = ($this->includeDate ? $this->getDateFormat() : '') . ($this->includeTime ? $this->getTimeFormat() : '');
        $format = preg_replace('/[.\-:\/ ]/', '', $format);

        $row = [];
        foreach (str_split($format) as $char) {
            $row[$char] = $available[$char];
        }

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
                'label' => Craft::t('formie', 'Year'),
                'handle' => 'year',
            ],
            [
                'label' => Craft::t('formie', 'Month'),
                'handle' => 'month',
            ],
            [
                'label' => Craft::t('formie', 'Day'),
                'handle' => 'day',
            ],
            [
                'label' => Craft::t('formie', 'Hour'),
                'handle' => 'hour',
            ],
            [
                'label' => Craft::t('formie', 'Minute'),
                'handle' => 'minute',
            ],
            [
                'label' => Craft::t('formie', 'Second'),
                'handle' => 'second',
            ],
            [
                'label' => Craft::t('formie', 'AM/PM'),
                'handle' => 'ampm',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        // Keep to disable trait validation on subfields.
        return parent::getElementValidationRules();
    }

    /**
     * @inheritDoc
     */
    public function getIsTextInput(): bool
    {
        return $this->displayType === 'calendar';
    }

    /**
     * @inheritDoc
     */
    public function getIsFieldset(): bool
    {
        return $this->displayType !== 'calendar';
    }

    public function getIsDate(): bool
    {
        return !$this->includeTime && $this->includeDate;
    }

    public function getIsTime(): bool
    {
        return $this->includeTime && !$this->includeDate;
    }

    public function getIsDateTime(): bool
    {
        return $this->includeTime && $this->includeDate;
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/date/input', [
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
        return Craft::$app->getView()->renderTemplate('formie/_formfields/date/preview', [
            'field' => $this,
        ]);
    }

    /**
     * Returns an array of month names.
     *
     * @return array
     */
    public function getMonths(): array
    {
        $months = [];

        foreach (Craft::$app->getLocale()->getMonthNames() as $index => $monthName) {
            $months[] = ['value' => $index + 1, 'label' => $monthName];
        }

        return $months;
    }

    public function getDefaultDate(): ?string
    {
        // An alias for `defaultValue` for GQL, as `defaultValue` returns a date, not string
        return $this->defaultValue;
    }

    public function getFrontEndJsModules(): ?array
    {
        if ($this->displayType === 'calendar' && $this->useDatePicker) {
            $locale = Craft::$app->getLocale()->id;

            // Handle language variants
            if (preg_match('/^([a-z]{2})-/', $locale, $matches)) {
                $locale = $matches[1];
            }

            $supportedLocales = ['ar', 'at', 'az', 'be', 'bg', 'bn', 'cat', 'cs', 'cy', 'da', 'de', 'eo', 'es', 'et', 'fa', 'fi', 'fo', 'fr', 'gr', 'he', 'hi', 'hr', 'hu', 'id', 'is', 'it', 'ja', 'km', 'ko', 'kz', 'lt', 'lv', 'mk', 'mn', 'ms', 'my', 'nl', 'no', 'pa', 'pl', 'pt', 'ro', 'ru', 'si', 'sk', 'sl', 'sq', 'sr-cyr', 'sr', 'sv', 'th', 'tr', 'uk', 'vn', 'zh-tw', 'zh'];

            if (in_array(strtolower($locale), $supportedLocales, true)) {
                $locale = strtolower($locale);
            }

            $minDate = null;
            $maxDate = null;

            if ($minDate = DateTimeHelper::toDateTime($this->minDate)) {
                $minDate = $minDate->format($this->getDateFormat());
            }

            if ($maxDate = DateTimeHelper::toDateTime($this->maxDate)) {
                $maxDate = $maxDate->format($this->getDateFormat());
            }

            // Ensure date picker option values are parsed for JSON
            $datePickerOptions = $this->datePickerOptions ?? [];

            foreach ($datePickerOptions as $key => $option) {
                $datePickerOptions[$key]['value'] = Json::decodeIfJson($option['value']);
            }

            return [
                'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/fields/date-picker.js', true),
                'module' => 'FormieDatePicker',
                'settings' => [
                    'datePickerOptions' => $datePickerOptions,
                    'dateFormat' => $this->getDateFormat(),
                    'timeFormat' => $this->getTimeFormat(),
                    'includeTime' => $this->includeTime,
                    'includeDate' => $this->includeDate,
                    'getIsDate' => $this->getIsDate(),
                    'getIsTime' => $this->getIsTime(),
                    'getIsDateTime' => $this->getIsDateTime(),
                    'locale' => $locale,
                    'minDate' => $minDate,
                    'maxDate' => $maxDate,
                ],
            ];
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function defineGeneralSchema(): array
    {
        $toggleBlocks = [];

        foreach ($this->getSubfieldOptions() as $key => $nestedField) {
            $subfields = [
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Label'),
                    'help' => Craft::t('formie', 'The label that describes this field.'),
                    'name' => $nestedField['handle'] . 'Label',
                    'validation' => 'requiredIfNotEqual:displayType=calendar',
                    'required' => true,
                ]),
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Placeholder'),
                    'help' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                    'name' => $nestedField['handle'] . 'Placeholder',
                ]),
            ];

            $condition = in_array($nestedField['handle'], ['year', 'month', 'day']) ? 'settings.includeDate' : 'settings.includeTime';

            $toggleBlocks[] = SchemaHelper::toggleContainer($condition, [
                SchemaHelper::toggleBlock([
                    'blockLabel' => $nestedField['label'],
                    'blockHandle' => $nestedField['handle'],
                    'showEnabled' => false,
                ], $subfields),
            ]);
        }

        return [
            SchemaHelper::labelField(),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Include Date'),
                'help' => Craft::t('formie', 'Whether this field should include the date.'),
                'name' => 'includeDate',
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Include Time'),
                'help' => Craft::t('formie', 'Whether this field should include the time.'),
                'name' => 'includeTime',
            ]),
            SchemaHelper::toggleContainer('settings.includeTime', [
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Time Label'),
                    'help' => Craft::t('formie', 'The label shown for the time field.'),
                    'name' => 'timeLabel',
                ]),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Default Value'),
                'help' => Craft::t('formie', 'Select a default value for this field.'),
                'name' => 'defaultOption',
                'options' => [
                    ['label' => Craft::t('formie', 'None'), 'value' => ''],
                    ['label' => Craft::t('formie', 'Today‘s Date/Time'), 'value' => 'today'],
                    ['label' => Craft::t('formie', 'Specific Date/Time'), 'value' => 'date'],
                ],
            ]),
            SchemaHelper::toggleContainer('settings.defaultOption=date', [
                SchemaHelper::dateField([
                    'label' => Craft::t('formie', 'Default Date/Time'),
                    'help' => Craft::t('formie', 'Entering a default value will place the value in the field when it loads.'),
                    'name' => 'defaultValue',
                ]),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Display Type'),
                'help' => Craft::t('formie', 'Set different display layouts for this field.'),
                'name' => 'displayType',
                'options' => [
                    ['label' => Craft::t('formie', 'Calendar'), 'value' => 'calendar'],
                    ['label' => Craft::t('formie', 'Dropdowns'), 'value' => 'dropdowns'],
                    ['label' => Craft::t('formie', 'Text Inputs'), 'value' => 'inputs'],
                ],
            ]),
            SchemaHelper::toggleContainer('!settings.displayType=calendar', $toggleBlocks),
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
            SchemaHelper::toggleContainer('settings.displayType=calendar', [
                SchemaHelper::dateField([
                    'label' => Craft::t('formie', 'Min Date'),
                    'help' => Craft::t('formie', 'Set a minimum date for dates to be picked from.'),
                    'name' => 'minDate',
                ]),
                SchemaHelper::dateField([
                    'label' => Craft::t('formie', 'Max Date'),
                    'help' => Craft::t('formie', 'Set a maximum date for dates to be picked up to.'),
                    'name' => 'maxDate',
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
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::toggleContainer('!settings.displayType=calendar', [
                SchemaHelper::subfieldLabelPosition(),
            ]),
            SchemaHelper::toggleContainer('settings.includeDate', [
                SchemaHelper::selectField([
                    'label' => Craft::t('formie', 'Date Format'),
                    'help' => Craft::t('formie', 'Select what format to present dates as.'),
                    'name' => 'dateFormat',
                    'options' => [
                        ['label' => 'YYYY-MM-DD', 'value' => 'Y-m-d'],
                        ['label' => 'MM-DD-YYYY', 'value' => 'm-d-Y'],
                        ['label' => 'DD-MM-YYYY', 'value' => 'd-m-Y'],
                        ['label' => 'YYYY/MM/DD', 'value' => 'Y/m/d'],
                        ['label' => 'MM/DD/YYYY', 'value' => 'm/d/Y'],
                        ['label' => 'DD/MM/YYYY', 'value' => 'd/m/Y'],
                        ['label' => 'YYYY.MM.DD', 'value' => 'Y.m.d'],
                        ['label' => 'MM.DD.YYYY', 'value' => 'm.d.Y'],
                        ['label' => 'DD.MM.YYYY', 'value' => 'd.m.Y'],
                    ],
                ]),
            ]),
            SchemaHelper::toggleContainer('settings.includeTime', [
                SchemaHelper::selectField([
                    'label' => Craft::t('formie', 'Time Format'),
                    'help' => Craft::t('formie', 'Select what format to present dates as.'),
                    'name' => 'timeFormat',
                    'options' => [
                        ['label' => '23:59:59 (HH:M:S)', 'value' => 'H:i:s'],
                        ['label' => '03:59:59 PM (H:M:S AM/PM)', 'value' => 'H:i:s A'],
                        ['label' => '23:59 (HH:M)', 'value' => 'H:i'],
                        ['label' => '03:59 PM (H:M AM/PM)', 'value' => 'H:i A'],
                        ['label' => '59:59 (M:S)', 'value' => 'i:s'],
                    ],
                ]),
            ]),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
            SchemaHelper::toggleContainer('settings.displayType=calendar', [
                SchemaHelper::lightswitchField([
                    'label' => Craft::t('formie', 'Use Date Picker'),
                    'help' => Craft::t('formie', 'Whether this field should use the bundled cross-browser date picker ([Flatpickr.js docs](https://flatpickr.js.org)) when rendering this field.'),
                    'name' => 'useDatePicker',
                ]),
                SchemaHelper::toggleContainer('settings.useDatePicker', [
                    SchemaHelper::tableField([
                        'label' => Craft::t('formie', 'Date Picker Options'),
                        'help' => Craft::t('formie', 'Add any additional options for the date picker to use. For available options, refer to the [Flatpickr.js docs](https://flatpickr.js.org/options/).'),
                        'validation' => 'min:0',
                        'newRowDefaults' => [
                            'label' => '',
                            'value' => '',
                        ],
                        'generateValue' => false,
                        'columns' => [
                            [
                                'type' => 'label',
                                'label' => 'Option',
                                'class' => 'singleline-cell textual',
                            ],
                            [
                                'type' => 'value',
                                'label' => 'Value',
                                'class' => 'singleline-cell textual',
                            ],
                        ],
                        'name' => 'datePickerOptions',
                    ]),
                ]),
            ]),
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
            SchemaHelper::toggleContainer('settings.displayType=calendar', [
                SchemaHelper::inputAttributesField(),
            ]),
        ];
    }

    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getContentGqlType(): array|Type
    {
        return DateTimeType::getType();
    }

    /**
     * @inheritdoc
     */
    public function getContentGqlMutationArgumentType(): array|Type
    {
        return [
            'name' => $this->handle,
            'type' => DateTimeType::getType(),
            'description' => $this->instructions,
        ];
    }


    // Protected Methods
    // =========================================================================

    protected function defineValueAsString($value, ElementInterface $element = null): string
    {
        if ($value instanceof DateTime) {
            $format = null;

            if ($this->getIsDateTime()) {
                $format = $this->getDateFormat() . ' ' . $this->getTimeFormat();
            }

            if ($this->getIsTime()) {
                $format = $this->getTimeFormat();
            }

            if ($this->getIsDate()) {
                $format = $this->getDateFormat();
            }

            return $value->format($format);
        }

        return '';
    }

    protected function defineValueAsJson($value, ElementInterface $element = null): mixed
    {
        return $this->getValueAsString($value, $element);
    }

    /**
     * @inheritDoc
     */
    protected function defineValueForIntegration($value, $integrationField, $integration, ElementInterface $element = null, $fieldKey = ''): mixed
    {
        // If a string value is requested for a date, return the ISO 8601 date string
        if ($integrationField->getType() === IntegrationField::TYPE_STRING) {
            if (!$this->getIsTime()) {
                return $value->format('c');
            }
        }

        // Fetch the default handling
        return parent::defineValueForIntegration($value, $integrationField, $integration, $element);
    }

    /**
     * @inheritDoc
     */
    protected function getSettingGqlType($attribute, $type, $fieldInfo): ListOfType|ScalarType|array
    {
        // Disable normal `defaultValue` as it is a DateTime, not string. We can't have the same attributes 
        // return multiple types. Instead, return `defaultDate` as the attribute name and correct type.
        if ($attribute === 'defaultValue') {
            return [
                'name' => 'defaultDate',
                'type' => DateTimeType::getType(),
            ];
        }

        return parent::getSettingGqlType($attribute, $type, $fieldInfo);
    }
}
