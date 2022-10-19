<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\SubfieldInterface;
use verbb\formie\base\SubfieldTrait;
use verbb\formie\events\ModifyDateTimeFormatEvent;
use verbb\formie\events\RegisterDateTimeFormatOpionsEvent;
use verbb\formie\events\ModifyFrontEndSubfieldsEvent;
use verbb\formie\gql\types\generators\FieldAttributeGenerator;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\Settings;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\gql\types\DateTime as DateTimeType;
use craft\helpers\Component;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\i18n\Locale;

use GraphQL\Type\Definition\Type;

use yii\base\Event;
use yii\db\Schema;

use DateTime;

class Date extends FormField implements SubfieldInterface, PreviewableFieldInterface
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_FRONT_END_SUBFIELDS = 'modifyFrontEndSubfields';
    public const EVENT_MODIFY_DATE_FORMAT = 'modifyDateFormat';
    public const EVENT_MODIFY_TIME_FORMAT = 'modifyTimeFormat';
    public const EVENT_REGISTER_DATE_FORMAT_OPTIONS = 'registerDateFormatOptions';
    public const EVENT_REGISTER_TIME_FORMAT_OPTIONS = 'registerTimeFormatOptions';


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
    public bool $includeTime = true;
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
    public string $minDateOption = '';
    public ?DateTime $minDate = null;
    public string $minDateOffset = 'add';
    public int $minDateOffsetNumber = 0;
    public string $minDateOffsetType = 'days';
    public string $maxDateOption = '';
    public ?DateTime $maxDate = null;
    public string $maxDateOffset = 'add';
    public int $maxDateOffsetNumber = 0;
    public string $maxDateOffsetType = 'days';
    public int $minYearRange = 100;
    public int $maxYearRange = 100;
    public mixed $availableDaysOfWeek = '*';


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        if (isset($config['minDate'])) {
            $config['minDate'] = self::toDateTime($config['minDate']) ?: null;
        }

        if (isset($config['maxDate'])) {
            $config['maxDate'] = self::toDateTime($config['maxDate']) ?: null;
        }

        parent::__construct($config);
    }

    public function init(): void
    {
        parent::init();

        if ($this->defaultOption === 'date') {
            if ($this->defaultValue && !$this->defaultValue instanceof DateTime) {
                // Assume setting to system time for this instance
                $defaultValue = DateTimeHelper::toDateTime($this->defaultValue, false, true);

                if ($defaultValue) {
                    $this->defaultValue = $defaultValue;
                }
            } else {
                $this->defaultValue = null;
            }
        } else if ($this->defaultOption === 'today') {
            // Assume setting to system time for this instance
            $this->defaultValue = DateTimeHelper::toDateTime(new DateTime(), false, true);
        } else {
            $this->defaultValue = null;
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
        // Allow plugins to modify the time format, commonly for specific sites
        $event = new ModifyDateTimeFormatEvent([
            'field' => $this,
            'timeFormat' => $this->timeFormat,
        ]);

        Event::trigger(static::class, self::EVENT_MODIFY_TIME_FORMAT, $event);

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

    public function getDefaultValue($attributePrefix = '')
    {
        $defaultValue = parent::getDefaultValue($attributePrefix);

        // Ensure default values are treated the same way as normal values
        return $this->normalizeValue($defaultValue);
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
                    StringHelper::padLeft(($value['year'] ?? '0000'), 4, '0'),
                    StringHelper::padLeft(($value['month'] ?? '00'), 2, '0'),
                    StringHelper::padLeft(($value['day'] ?? '00'), 2, '0'),
                ]);

                $dateTime['time'] = implode(':', [
                    StringHelper::padLeft(($value['hour'] ?? '00'), 2, '0'),
                    StringHelper::padLeft(($value['minute'] ?? '00'), 2, '0'),
                    StringHelper::padLeft(($value['ssecond'] ?? '00'), 2, '0'),
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
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();
        $displayType = $settings->defaultDateDisplayType ?: 'calendar';
        $defaultOption = $settings->defaultDateValueOption ?: '';
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
            'availableDaysOfWeek' => '*',
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFormattingChar($name): ?string
    {
        $formattingMap = [
            'year' => 'Y',
            'month' => 'm',
            'day' => 'd',
            'hour' => 'H',
            'minute' => 'i',
            'second' => 's',
            'ampm' => 'A',
        ];

        return $formattingMap[$name] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndSubfields($context): array
    {
        $subFields = [];
        $rowConfigs = [];

        if (!$this->useDatePicker && $this->displayType == 'calendar') {
            if ($this->includeDate) {
                $rowConfigs[0][] = [
                    'type' => SingleLineText::class,
                    'name' => $this->name,
                    'handle' => 'date',
                    'required' => $this->required,
                    'placeholder' => $this->placeholder,
                    'errorMessage' => $this->errorMessage,
                    'defaultValue' => $this->defaultValue,
                    'labelPosition' => $this->subfieldLabelPosition,
                    'inputAttributes' => [
                        [
                            'label' => 'type',
                            'value' => 'date',
                        ],
                        [
                            'label' => 'autocomplete',
                            'value' => 'off',
                        ],
                    ],
                ];
            }

            if ($this->includeTime) {
                $rowConfigs[0][] = [
                    'type' => SingleLineText::class,
                    'name' => $this->timeLabel,
                    'handle' => 'time',
                    'required' => $this->required,
                    'placeholder' => $this->placeholder,
                    'errorMessage' => $this->errorMessage,
                    'defaultValue' => $this->defaultValue,
                    'labelPosition' => $this->subfieldLabelPosition,
                    'inputAttributes' => [
                        [
                            'label' => 'type',
                            'value' => 'time',
                        ],
                        [
                            'label' => 'autocomplete',
                            'value' => 'off',
                        ],
                    ],
                ];
            }
        }

        if ($this->displayType == 'inputs' || $this->displayType == 'dropdowns') {
            // Split the format into an array, so we can only show the fields we need to for dropdowns/inputs
            $format = ($this->includeDate ? $this->getDateFormat() : '') . ($this->includeTime ? $this->getTimeFormat() : '');
            $format = preg_replace('/[.\-:\/ ]/', '', $format);
            $formattingMap = str_split($format);

            $minYear = $this->_getYearOptions()[1]['value'];
            $maxYear = $this->_getYearOptions()[count($this->_getYearOptions()) - 1]['value'];

            // Setup definitions for each portion of the date-formatted string `Y-m-d H/h:i:s A`.
            $inputConfigs = [
                'Y' => [
                    'type' => Number::class,
                    'name' => $this->yearLabel,
                    'handle' => 'year',
                    'placeholder' => $this->yearPlaceholder,
                    'labelPosition' => $this->subfieldLabelPosition,
                    'min' => $minYear,
                    'max' => $maxYear,
                ],
                'm' => [
                    'type' => Number::class,
                    'name' => $this->monthLabel,
                    'handle' => 'month',
                    'placeholder' => $this->monthPlaceholder,
                    'labelPosition' => $this->subfieldLabelPosition,
                    'min' => 1,
                    'max' => 12,
                ],
                'd' => [
                    'type' => Number::class,
                    'name' => $this->dayLabel,
                    'handle' => 'day',
                    'placeholder' => $this->dayPlaceholder,
                    'labelPosition' => $this->subfieldLabelPosition,
                    'min' => 1,
                    'max' => 31,
                ],
                'H' => [
                    'type' => Number::class,
                    'name' => $this->hourLabel,
                    'handle' => 'hour',
                    'placeholder' => $this->hourPlaceholder,
                    'labelPosition' => $this->subfieldLabelPosition,
                    'min' => 0,
                    'max' => 23,
                ],
                'h' => [
                    'type' => Number::class,
                    'name' => $this->hourLabel,
                    'handle' => 'hour',
                    'placeholder' => $this->hourPlaceholder,
                    'labelPosition' => $this->subfieldLabelPosition,
                    'min' => 0,
                    'max' => 12,
                ],
                'i' => [
                    'type' => Number::class,
                    'name' => $this->minuteLabel,
                    'handle' => 'minute',
                    'placeholder' => $this->minutePlaceholder,
                    'labelPosition' => $this->subfieldLabelPosition,
                    'min' => 0,
                    'max' => 59,
                ],
                's' => [
                    'type' => Number::class,
                    'name' => $this->secondLabel,
                    'handle' => 'second',
                    'placeholder' => $this->secondPlaceholder,
                    'labelPosition' => $this->subfieldLabelPosition,
                    'min' => 0,
                    'max' => 59,
                ],
                'A' => [
                    'type' => Dropdown::class,
                    'name' => $this->ampmLabel,
                    'handle' => 'ampm',
                    'placeholder' => $this->ampmPlaceholder,
                    'labelPosition' => $this->subfieldLabelPosition,
                    'options' => [
                        ['value' => 'AM', 'label' => Craft::t('formie', 'AM')],
                        ['value' => 'PM', 'label' => Craft::t('formie', 'PM')],
                    ],
                ],
            ];

            $dropdownConfigs = [
                'Y' => [
                    'type' => Dropdown::class,
                    'name' => $this->yearLabel,
                    'handle' => 'year',
                    'placeholder' => $this->yearPlaceholder,
                    'labelPosition' => $this->subfieldLabelPosition,
                    'options' => $this->_getYearOptions(),
                ],
                'm' => [
                    'type' => Dropdown::class,
                    'name' => $this->monthLabel,
                    'handle' => 'month',
                    'placeholder' => $this->monthPlaceholder,
                    'labelPosition' => $this->subfieldLabelPosition,
                    'options' => $this->_getMonthOptions(),
                ],
                'd' => [
                    'type' => Dropdown::class,
                    'name' => $this->dayLabel,
                    'handle' => 'day',
                    'placeholder' => $this->dayPlaceholder,
                    'labelPosition' => $this->subfieldLabelPosition,
                    'options' => $this->_generateOptions(1, 31),
                ],
                'H' => [
                    'type' => Dropdown::class,
                    'name' => $this->hourLabel,
                    'handle' => 'hour',
                    'placeholder' => $this->hourPlaceholder,
                    'labelPosition' => $this->subfieldLabelPosition,
                    'options' => $this->_generateOptions(0, 23),
                ],
                'h' => [
                    'type' => Dropdown::class,
                    'name' => $this->hourLabel,
                    'handle' => 'hour',
                    'placeholder' => $this->hourPlaceholder,
                    'labelPosition' => $this->subfieldLabelPosition,
                    'options' => $this->_generateOptions(0, 12),
                ],
                'i' => [
                    'type' => Dropdown::class,
                    'name' => $this->minuteLabel,
                    'handle' => 'minute',
                    'placeholder' => $this->minutePlaceholder,
                    'labelPosition' => $this->subfieldLabelPosition,
                    'options' => $this->_generateOptions(1, 59),
                ],
                's' => [
                    'type' => Dropdown::class,
                    'name' => $this->secondLabel,
                    'handle' => 'second',
                    'placeholder' => $this->secondPlaceholder,
                    'labelPosition' => $this->subfieldLabelPosition,
                    'options' => $this->_generateOptions(1, 59),
                ],
                'A' => [
                    'type' => Dropdown::class,
                    'name' => $this->ampmLabel,
                    'handle' => 'ampm',
                    'placeholder' => $this->ampmPlaceholder,
                    'labelPosition' => $this->subfieldLabelPosition,
                    'options' => [
                        ['value' => 'AM', 'label' => Craft::t('formie', 'AM')],
                        ['value' => 'PM', 'label' => Craft::t('formie', 'PM')],
                    ],
                ],
            ];

            // For each part of the datetime format string, apply the correct config
            foreach ($formattingMap as $char) {
                if ($this->displayType == 'inputs') {
                    $rowConfigs[0][] = $inputConfigs[$char] ?? null;
                }

                if ($this->displayType == 'dropdowns') {
                    $rowConfigs[0][] = $dropdownConfigs[$char] ?? null;
                }
            }
        }

        foreach ($rowConfigs as $key => $rowConfig) {
            foreach ($rowConfig as $config) {
                $subField = Component::createComponent($config, FormFieldInterface::class);

                // Ensure we set the parent field instance to handle the nested nature of subfields
                $subField->setParentField($this);

                $subFields[$key][] = $subField;
            }
        }

        $event = new ModifyFrontEndSubfieldsEvent([
            'field' => $this,
            'rows' => $subFields,
        ]);

        Event::trigger(static::class, self::EVENT_MODIFY_FRONT_END_SUBFIELDS, $event);

        return $event->rows;
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

    public function getMinDate()
    {
        if ($this->minDateOption === 'today') {
            $operator = $this->minDateOffset === 'add' ? '+' : '-';
            $interval = "{$operator}{$this->minDateOffsetNumber} {$this->minDateOffsetType}";

            return self::toDateTime(DateTimeHelper::now())->modify($interval)->setTime(0, 0, 0);
        }

        if ($this->minDateOption === 'date' && $this->minDate) {
            return $this->minDate->setTime(0, 0, 0);
        }

        return null;
    }

    public function getMaxDate()
    {
        if ($this->maxDateOption === 'today') {
            $operator = $this->maxDateOffset === 'add' ? '+' : '-';
            $interval = "{$operator}{$this->maxDateOffsetNumber} {$this->maxDateOffsetType}";

            return self::toDateTime(DateTimeHelper::now())->modify($interval)->setTime(23, 59, 59);
        }

        if ($this->maxDateOption === 'date' && $this->maxDate) {
            return $this->maxDate->setTime(23, 59, 59);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        // Keep to disable trait validation on subfields.
        $rules = parent::getElementValidationRules();
        $rules[] = [$this->handle, 'validateDateValues'];

        return $rules;
    }

    public function validateDateValues(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->handle);

        if ($normalized = (!$value instanceof DateTime)) {
            $value = DateTimeHelper::toDateTime($value);
        }

        if (!$value) {
            $element->addError($this->handle, Craft::t('formie', 'Value must be a date.'));
            return;
        }

        if ($min = $this->getMinDate()) {
            if ($value < $min) {
                $element->addError($this->handle, Craft::t('formie', 'Value must be no earlier than {min}.', [
                    'min' => Craft::$app->getFormatter()->asDate($min, Locale::LENGTH_SHORT),
                ]));
            }
        }

        if ($max = $this->getMaxDate()) {
            if ($value > $max) {
                $element->addError($this->handle, Craft::t('formie', 'Value must be no later than {max}.', [
                    'max' => Craft::$app->getFormatter()->asDate($max, Locale::LENGTH_SHORT),
                ]));
            }
        }

        if ($normalized) {
            // Update the value on the model to the DateTime object
            $model->$attribute = $value;
        }
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

            $minDate = $this->getMinDate();
            $maxDate = $this->getMaxDate();

            if ($minDate) {
                $minDate = $minDate->format('Y-m-d H:i:s');
            }

            if ($maxDate) {
                $maxDate = $maxDate->format('Y-m-d H:i:s');
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
                    'availableDaysOfWeek' => $this->availableDaysOfWeek,
                ],
            ];
        }

        return null;
    }

    public function getWeekDayNamesOptions()
    {
        $options = [['label' => Craft::t('formie', 'All'), 'value' => '*']];

        foreach (Craft::$app->getLocale()->getWeekDayNames(Locale::LENGTH_FULL) as $key => $value) {
            $options[] = ['label' => $value, 'value' => $key];
        }

        return $options;
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
                    'validation' => 'required',
                    'required' => true,
                ]),
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Placeholder'),
                    'help' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                    'name' => $nestedField['handle'] . 'Placeholder',
                ]),
            ];

            $condition = in_array($nestedField['handle'], ['year', 'month', 'day']) ? '$get(includeDate).value' : '$get(includeTime).value';
            $conditions = array_filter(['$get(displayType).value != calendar', $condition]);

            $toggleBlock = SchemaHelper::toggleBlock([
                'blockLabel' => $nestedField['label'],
                'blockHandle' => $nestedField['handle'],
                'showEnabled' => false,
            ], $subfields);

            $toggleBlock['if'] = implode(' && ', $conditions);

            $toggleBlocks[] = $toggleBlock;
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
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Time Label'),
                'help' => Craft::t('formie', 'The label shown for the time field.'),
                'name' => 'timeLabel',
                'if' => '$get(includeTime).value',
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
            SchemaHelper::dateField([
                'label' => Craft::t('formie', 'Default Date/Time'),
                'help' => Craft::t('formie', 'Entering a default value will place the value in the field when it loads.'),
                'name' => 'defaultValue',
                'if' => '$get(defaultOption).value == date',
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
            SchemaHelper::toggleBlocks([
                'subfields' => $this->getSubfieldOptions(),
            ], $toggleBlocks),
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
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Min Date'),
                'help' => Craft::t('formie', 'Set a minimum date for dates to be picked from.'),
                'name' => 'minDateOption',
                'if' => '$get(displayType).value == calendar',
                'options' => [
                    ['label' => Craft::t('formie', 'None'), 'value' => ''],
                    ['label' => Craft::t('formie', 'Today‘s Date/Time'), 'value' => 'today'],
                    ['label' => Craft::t('formie', 'Specific Date/Time'), 'value' => 'date'],
                ],
            ]),
            SchemaHelper::dateField([
                'label' => Craft::t('formie', 'Min Date'),
                'help' => Craft::t('formie', 'Set a minimum date for dates to be picked from.'),
                'name' => 'minDate',
                'if' => '$get(minDateOption).value == date',
            ]),
            [
                '$formkit' => 'fieldWrap',
                'label' => Craft::t('formie', 'Offset'),
                'help' => Craft::t('formie', 'Enter an optional offset for today‘s date.'),
                'if' => '$get(minDateOption).value == today',
                'children' => [
                    [
                        '$el' => 'div',
                        'attrs' => [
                            'class' => 'flex',
                        ],
                        'children' => [
                            SchemaHelper::selectField([
                                'name' => 'minDateOffset',
                                'options' => [
                                    ['label' => Craft::t('formie', 'Add'), 'value' => 'add'],
                                    ['label' => Craft::t('formie', 'Subtract'), 'value' => 'subtract'],
                                ],
                            ]),
                            SchemaHelper::numberField([
                                'name' => 'minDateOffsetNumber',
                                'inputClass' => 'text flex-grow',
                            ]),
                            SchemaHelper::selectField([
                                'name' => 'minDateOffsetType',
                                'options' => [
                                    ['label' => Craft::t('formie', 'Days'), 'value' => 'days'],
                                    ['label' => Craft::t('formie', 'Weeks'), 'value' => 'weeks'],
                                    ['label' => Craft::t('formie', 'Months'), 'value' => 'months'],
                                    ['label' => Craft::t('formie', 'Years'), 'value' => 'years'],
                                ],
                            ]),
                        ],
                    ],
                ],
            ],
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Max Date'),
                'help' => Craft::t('formie', 'Set a maximum date for dates to be picked up to.'),
                'name' => 'maxDateOption',
                'if' => '$get(displayType).value == calendar',
                'options' => [
                    ['label' => Craft::t('formie', 'None'), 'value' => ''],
                    ['label' => Craft::t('formie', 'Today‘s Date/Time'), 'value' => 'today'],
                    ['label' => Craft::t('formie', 'Specific Date/Time'), 'value' => 'date'],
                ],
            ]),
            SchemaHelper::dateField([
                'label' => Craft::t('formie', 'Max Date'),
                'help' => Craft::t('formie', 'Set a maximum date for dates to be picked up to.'),
                'name' => 'maxDate',
                'if' => '$get(maxDateOption).value == date',
            ]),
            [
                '$formkit' => 'fieldWrap',
                'label' => Craft::t('formie', 'Offset'),
                'help' => Craft::t('formie', 'Enter an optional offset for today‘s date.'),
                'if' => '$get(maxDateOption).value == today',
                'children' => [
                    [
                        '$el' => 'div',
                        'attrs' => [
                            'class' => 'flex',
                        ],
                        'children' => [
                            SchemaHelper::selectField([
                                'name' => 'maxDateOffset',
                                'options' => [
                                    ['label' => Craft::t('formie', 'Add'), 'value' => 'add'],
                                    ['label' => Craft::t('formie', 'Subtract'), 'value' => 'subtract'],
                                ],
                            ]),
                            SchemaHelper::numberField([
                                'name' => 'maxDateOffsetNumber',
                                'inputClass' => 'text flex-grow',
                            ]),
                            SchemaHelper::selectField([
                                'name' => 'maxDateOffsetType',
                                'options' => [
                                    ['label' => Craft::t('formie', 'Days'), 'value' => 'days'],
                                    ['label' => Craft::t('formie', 'Weeks'), 'value' => 'weeks'],
                                    ['label' => Craft::t('formie', 'Months'), 'value' => 'months'],
                                    ['label' => Craft::t('formie', 'Years'), 'value' => 'years'],
                                ],
                            ]),
                        ],
                    ],
                ],
            ],
            SchemaHelper::checkboxSelectField([
                'label' => Craft::t('formie', 'Available Days'),
                'help' => Craft::t('formie', 'Choose which days of the week should be available.'),
                'name' => 'availableDaysOfWeek',
                'if' => '$get(displayType).value == calendar',
                'options' => $this->getWeekDayNamesOptions(),
                'showAllOption' => true,
            ]),
            [
                '$formkit' => 'fieldWrap',
                'label' => Craft::t('formie', 'Year Range'),
                'help' => Craft::t('formie', 'Set the range of years relative to this year that are available to select.'),
                'if' => '$get(displayType).value == dropdowns',
                'children' => [
                    [
                        '$el' => 'div',
                        'attrs' => [
                            'class' => 'flex',
                        ],
                        'children' => [
                            SchemaHelper::numberField([
                                'name' => 'minYearRange',
                                'inputClass' => 'text flex-grow',
                                'sections-schema' => [
                                    'prefix' => [
                                        '$el' => 'span',
                                        'attrs' => ['class' => 'fui-prefix-text'],
                                        'children' => Craft::t('formie', 'Start'),
                                    ],
                                ],
                            ]),
                            SchemaHelper::numberField([
                                'name' => 'maxYearRange',
                                'inputClass' => 'text flex-grow',
                                'sections-schema' => [
                                    'prefix' => [
                                        '$el' => 'span',
                                        'attrs' => ['class' => 'fui-prefix-text'],
                                        'children' => Craft::t('formie', 'End'),
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ],
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
            SchemaHelper::subfieldLabelPosition([
                'if' => '$get(displayType).value != calendar',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Date Format'),
                'help' => Craft::t('formie', 'Select what format to present dates as.'),
                'name' => 'dateFormat',
                'if' => '$get(includeDate).value',
                'options' => $this->_getDateFormatOptions(),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Time Format'),
                'help' => Craft::t('formie', 'Select what format to present dates as.'),
                'name' => 'timeFormat',
                'if' => '$get(includeTime).value',
                'options' => $this->_getTimeFormatOptions(),
            ]),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Use Date Picker'),
                'help' => Craft::t('formie', 'Whether this field should use the bundled cross-browser date picker ([Flatpickr.js docs](https://flatpickr.js.org)) when rendering this field.'),
                'name' => 'useDatePicker',
                'if' => '$get(displayType).value == calendar',
            ]),
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Date Picker Options'),
                'help' => Craft::t('formie', 'Add any additional options for the date picker to use. For available options, refer to the [Flatpickr.js docs](https://flatpickr.js.org/options/).'),
                'validation' => 'min:0',
                'if' => '$get(displayType).value == calendar && $get(useDatePicker).value',
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
            SchemaHelper::inputAttributesField([
                'if' => '$get(displayType).value == calendar',
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

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            // We're force to use a string-representation of the default value, due to the parent `defaultValue` definition
            // So cast it properly here as a string, but also provide `defaultDate` as the proper type.
            'defaultValue' => [
                'name' => 'defaultValue',
                'type' => Type::string(),
                'resolve' => function($field) {
                    return (string)$field->defaultValue;
                },
            ],
            'defaultDate' => [
                'name' => 'defaultDate',
                'type' => DateTimeType::getType(),
            ],
            'minDate' => [
                'name' => 'minDate',
                'type' => DateTimeType::getType(),
            ],
            'maxDate' => [
                'name' => 'maxDate',
                'type' => DateTimeType::getType(),
            ],
            'datePickerOptions' => [
                'name' => 'datePickerOptions',
                'type' => Type::listOf(FieldAttributeGenerator::generateType()),
            ],
        ]);
    }

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        $form = $context['form'] ?? null;
        $errors = $context['errors'] ?? null;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        // If using multiple fields, switch to fieldset. Basically anything other than a datepicker
        if (!$this->useDatePicker) {
            if ($key === 'fieldContainer') {
                return new HtmlTag('fieldset', [
                    'class' => 'fui-fieldset fui-subfield-fieldset',
                ]);
            }

            if ($key === 'fieldLabel') {
                // Don't show the label for calendars, they take care of themselves
                if ($this->displayType == 'calendar') {
                    return null;
                }

                return new HtmlTag('legend', [
                    'class' => 'fui-legend',
                ]);
            }
        }

        if ($key === 'fieldInput' && $this->useDatePicker && $this->displayType == 'calendar') {
            return new HtmlTag('input', array_merge([
                'type' => 'text',
                'id' => $id,
                'class' => [
                    'fui-input',
                    $errors ? 'fui-error' : false,
                ],
                'name' => $this->getHtmlName('datetime'),
                'placeholder' => Craft::t('formie', $this->placeholder) ?: null,
                'required' => $this->required ? true : null,
                'autocomplete' => 'off',
                'data' => [
                    'fui-id' => $dataId,
                    'fui-message' => Craft::t('formie', $this->errorMessage) ?: null,
                ],
                'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
            ], $this->getInputAttributes()));
        }

        return parent::defineHtmlTag($key, $context);
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

    protected function defineValueForIntegration($value, $integrationField, $integration, ElementInterface $element = null, $fieldKey = ''): mixed
    {
        // If a string value is requested for a date, return the ISO 8601 date string
        if ($integrationField->getType() === IntegrationField::TYPE_STRING) {
            if (!$this->getIsTime()) {
                if ($value instanceof DateTime) {
                    return $value->format('c');
                }

                return $value;
            }
        }

        // Fetch the default handling
        return parent::defineValueForIntegration($value, $integrationField, $integration, $element);
    }


    // Private Methods
    // =========================================================================

    /**
     * Returns an array of numbers between a provided start and end number.
     *
     * @return array
     */
    private function _generateOptions($start, $end)
    {
        $options = [['value' => '', 'label' => '', 'disabled' => true]];

        for ($i = $start; $i <= $end; $i++) { 
            $options[] = ['label' => $i, 'value' => $i];
        }

        return $options;
    }

    /**
     * Returns an array of month names.
     *
     * @return array
     */
    private function _getMonthOptions(): array
    {
        $options = [['value' => '', 'label' => '', 'disabled' => true]];

        foreach (Craft::$app->getLocale()->getMonthNames() as $index => $monthName) {
            $options[] = ['value' => $index + 1, 'label' => $monthName];
        }

        return $options;
    }

    /**
     * Returns an array of years relative to the current year.
     *
     * @return array
     */
    private function _getYearOptions(): array
    {
        $defaultValue = $this->defaultValue ?: new DateTime();
        $year = (int)$defaultValue->format('Y');
        $minYear = $year - $this->minYearRange;
        $maxYear = $year + $this->maxYearRange;

        $options = [['value' => '', 'label' => '', 'disabled' => true]];

        for ($y = $minYear; $y < $maxYear; $y++) {
            $options[] = ['value' => $y, 'label' => $y];
        }

        return $options;
    }

    private function _getDateFormatOptions(): array
    {
        $options = [
            ['label' => 'YYYY-MM-DD', 'value' => 'Y-m-d'],
            ['label' => 'MM-DD-YYYY', 'value' => 'm-d-Y'],
            ['label' => 'DD-MM-YYYY', 'value' => 'd-m-Y'],
            ['label' => 'YYYY/MM/DD', 'value' => 'Y/m/d'],
            ['label' => 'MM/DD/YYYY', 'value' => 'm/d/Y'],
            ['label' => 'DD/MM/YYYY', 'value' => 'd/m/Y'],
            ['label' => 'YYYY.MM.DD', 'value' => 'Y.m.d'],
            ['label' => 'MM.DD.YYYY', 'value' => 'm.d.Y'],
            ['label' => 'DD.MM.YYYY', 'value' => 'd.m.Y'],
        ];

        $event = new RegisterDateTimeFormatOpionsEvent([
            'field' => $this,
            'options' => $options,
        ]);
        $this->trigger(self::EVENT_REGISTER_DATE_FORMAT_OPTIONS, $event);

        return $event->options;
    }

    private function _getTimeFormatOptions(): array
    {
        $options = [
            ['label' => '23:59:59 (HH:MM:SS)', 'value' => 'H:i:s'],
            ['label' => '03:59:59 PM (H:MM:SS AM/PM)', 'value' => 'h:i:s A'],
            ['label' => '23:59 (HH:MM)', 'value' => 'H:i'],
            ['label' => '03:59 PM (H:MM AM/PM)', 'value' => 'h:i A'],
            ['label' => '59:59 (MM:SS)', 'value' => 'i:s'],
        ];

        $event = new RegisterDateTimeFormatOpionsEvent([
            'field' => $this,
            'options' => $options,
        ]);
        $this->trigger(self::EVENT_REGISTER_TIME_FORMAT_OPTIONS, $event);

        return $event->options;
    }
}
