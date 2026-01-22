<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\SubFieldInterface;
use verbb\formie\base\SubField;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyDateTimeFormatEvent;
use verbb\formie\events\RegisterDateTimeFormatOptionsEvent;
use verbb\formie\events\ModifyFrontEndSubFieldsEvent;
use verbb\formie\fields\subfields\DateYear;
use verbb\formie\gql\types\generators\FieldAttributeGenerator;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\DateTimeHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\DateTime;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;
use verbb\formie\models\Settings;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\InlineEditableFieldInterface;
use craft\base\PreviewableFieldInterface;
use craft\base\SortableFieldInterface;
use craft\gql\types\DateTime as DateTimeType;
use craft\helpers\Component;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\i18n\Locale;

use Faker\Generator as FakerFactory;

use GraphQL\Type\Definition\Type;

use yii\base\Event;
use yii\db\Schema;
use yii\validators\RequiredValidator;
use yii\validators\Validator;

use DateTimeZone;

class Date extends SubField implements InlineEditableFieldInterface, PreviewableFieldInterface, SortableFieldInterface
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_DATE_FORMAT = 'modifyDateFormat';
    public const EVENT_MODIFY_TIME_FORMAT = 'modifyTimeFormat';
    public const EVENT_REGISTER_DATE_FORMAT_OPTIONS = 'registerDateFormatOptions';
    public const EVENT_REGISTER_TIME_FORMAT_OPTIONS = 'registerTimeFormatOptions';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Date/Time');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/date/icon.svg';
    }

    public static function toDateTime($value): DateTime|bool
    {
        // We should never deal with timezones
        return DateTimeHelper::toDateTime($value, false, false);
    }

    public static function dbType(): string
    {
        return Schema::TYPE_DATETIME;
    }


    // Properties
    // =========================================================================

    public string $dateFormat = 'Y-m-d';
    public string $timeFormat = 'H:i';
    public string $displayType = 'calendar';
    public ?string $defaultOption = null;
    public array $datePickerOptions = [];
    public string $minDateOption = '';
    public DateTime|string|null $minDate = null;
    public string $minDateOffset = 'add';
    public int $minDateOffsetNumber = 0;
    public string $minDateOffsetType = 'days';
    public string $maxDateOption = '';
    public DateTime|string|null $maxDate = null;
    public string $maxDateOffset = 'add';
    public int $maxDateOffsetNumber = 0;
    public string $maxDateOffsetType = 'days';
    public int $minYearRange = -100;
    public int $maxYearRange = 100;
    public mixed $availableDaysOfWeek = '*';


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        // Discard the layouts we use for the control panel
        if (array_key_exists('layouts', $config)) {
            unset($config['layouts']);
        }

        // Normalize date settings to ensure we strip timezones (they're saved without one)
        if (isset($config['minDate'])) {
            if (!($config['minDate'] instanceof DateTime)) {
                $config['minDate'] = DateTimeHelper::toDateTime($config['minDate'], false, false) ?: null;
            }
        }

        if (isset($config['maxDate'])) {
            if (!($config['maxDate'] instanceof DateTime)) {
                $config['maxDate'] = DateTimeHelper::toDateTime($config['maxDate'], false, false) ?: null;
            }
        }

        if (isset($config['defaultOption'])) {
            if (isset($config['defaultValue']) && $config['defaultOption'] === 'date') {
                if (!($config['defaultValue'] instanceof DateTime)) {
                    $config['defaultValue'] = DateTimeHelper::toDateTime($config['defaultValue'], false, false) ?: null;
                }
            } else if ($config['defaultOption'] === 'today') {
                $config['defaultValue'] = DateTimeHelper::toDateTime(new DateTime('today'), false, false);
            } else {
                $config['defaultValue'] = null;
            }
        } else {
            $config['defaultValue'] = null;
        }

        if (array_key_exists('useDatePicker', $config) && $config['useDatePicker']) {
            $config['displayType'] = 'datePicker';
        }

        unset(
            $config['dayLabel'],
            $config['dayPlaceholder'],
            $config['monthLabel'],
            $config['monthPlaceholder'],
            $config['yearLabel'],
            $config['yearPlaceholder'],
            $config['hourLabel'],
            $config['hourPlaceholder'],
            $config['minuteLabel'],
            $config['minutePlaceholder'],
            $config['secondLabel'],
            $config['secondPlaceholder'],
            $config['ampmLabel'],
            $config['ampmPlaceholder'],
            $config['minYearRange'],
            $config['maxYearRange'],
            $config['timeLabel'],
            $config['includeDate'],
            $config['includeTime'],
            $config['useDatePicker'],
        );

        // Prevent a required state set at the top-level field
        $config['required'] = false;

        parent::__construct($config);
    }

    public function getFieldTypeDefaults(): array
    {
        // Setup defaults for some values which can't be set in the property definition
        $config = parent::getFieldTypeDefaults();

        // Setup defaults from the plugin-level
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        if (!isset($config['displayType'])) {
            $config['displayType'] = $settings->defaultDateDisplayType ?: 'calendar';
        }

        if (!isset($config['defaultOption'])) {
            $config['defaultOption'] = $settings->defaultDateValueOption ?: '';
        }

        if (!isset($config['defaultValue'])) {
            $config['defaultValue'] = $settings->getDefaultDateTimeValue();
        }

        return $config;
    }

    public function getIsRequired(): ?bool
    {
        // Calendar-based fields might not have their sub-fields visible, but could be required. Best to show a required
        // state on the outer field.
        if (in_array($this->displayType, ['calendar', 'datePicker'])) {
            foreach ($this->getFields() as $field) {
                if ($field->getIsRequired()) {
                    return true;
                }
            }
        }

        // Nested fields themselves can't be required, only their inner fields can
        return parent::getIsRequired();
    }

    public function getFormBuilderSettings(): array
    {
        $settings = parent::getFormBuilderSettings();

        // Modify the field layout a little to include current and default layouts for each type.
        // Stored under `layouts` so that the `rows` value we save is separate.
        if ($this->displayType === 'dropdowns') {
            $settings['layouts']['dropdowns'] = $settings['rows'];
        } else {
            $fieldLayout = new FieldLayout();
            $fieldLayout->setPages([['rows' => $this->getDropdownSubFields()]]);
            $settings['layouts']['dropdowns'] = $fieldLayout->getFormBuilderConfig()[0]['rows'] ?? [];
        }

        if ($this->displayType === 'inputs') {
            $settings['layouts']['inputs'] = $settings['rows'];
        } else {
            $fieldLayout = new FieldLayout();
            $fieldLayout->setPages([['rows' => $this->getInputSubFields()]]);
            $settings['layouts']['inputs'] = $fieldLayout->getFormBuilderConfig()[0]['rows'] ?? [];
        }

        if ($this->displayType === 'calendar' || $this->displayType === 'datePicker') {
            $settings['layouts']['calendar'] = $settings['rows'];
        } else {
            $fieldLayout = new FieldLayout();
            $fieldLayout->setPages([['rows' => $this->getCalendarSubFields()]]);
            $settings['layouts']['calendar'] = $fieldLayout->getFormBuilderConfig()[0]['rows'] ?? [];
        }

        return $settings;
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

    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        $html = '';
        // Ensure that the timezone we use is UTC, as the dates are set in that. We don't want them converted
        $timeZone = Craft::$app->getFormatter()->timeZone;
        Craft::$app->getFormatter()->timeZone = 'UTC';

        if ($value) {
            if ($this->getIsDateTime()) {
                $html = Craft::$app->getFormatter()->asDatetime($value, Locale::LENGTH_SHORT);
            } else if ($this->getIsTime()) {
                $html = Craft::$app->getFormatter()->asTime($value, Locale::LENGTH_SHORT);
            } else if ($this->getIsDate()) {
                $html = Craft::$app->getFormatter()->asDate($value, Locale::LENGTH_SHORT);
            }
        }

        // Set the original timezone back, just in case
        Craft::$app->getFormatter()->timeZone = $timeZone;

        return $html;
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if (!$value || $value instanceof DateTime) {
            return $value;
        }

        // For dropdowns and inputs, we need to convert our array syntax to string
        if ($this->displayType === 'dropdowns' || $this->displayType === 'inputs') {
            if (is_array($value)) {
                $value = array_filter($value);

                $year = isset($value['year']) ? intval($value['year']) : null;
                $month = isset($value['month']) ? intval($value['month']) : null;
                $day = isset($value['day']) ? intval($value['day']) : null;
                $hour = isset($value['hour']) ? intval($value['hour']) : null;
                $minute = isset($value['minute']) ? intval($value['minute']) : null;
                $second = isset($value['second']) ? intval($value['second']) : null;

                // Handle any invalid dates
                if ($year === null || $month === null || $day === null) {
                    $value = null;
                } else {
                    $value = sprintf("%04d-%02d-%02d %02d:%02d:%02d", $year, $month, $day, $hour, $minute, $second);
                }
            }
        } else if ($this->displayType === 'calendar') {
            if (is_array($value)) {
                $value = array_filter($value);
            }
        } else if ($this->displayType === 'datePicker') {
            if (is_array($value)) {
                // Promote "date" or "time" to datetime if they contain a full datetime.
                foreach (['date', 'time'] as $k) {
                    if (isset($value[$k]) && $this->_isDateTimeString($value[$k])) {
                        $value['datetime'] = $value[$k];
                        unset($value[$k]);
                    }
                }

                // If we already have a datetime, leave it alone.
                // But if toDateTime normalises it further, update it.
                if ($dt = self::toDateTime($value)) {
                    $value['datetime'] = DateTimeHelper::toIso8601($dt);
                }
            }
        }

        if (($date = self::toDateTime($value)) !== false) {
            return $date;
        }

        return null;
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        // We don't actually store the value as separate fields, instead just a date.
        if ($value instanceof DateTime || DateTimeHelper::isIso8601($value)) {
            return Db::prepareDateForDb($value);
        }

        return $value;
    }

    public function getMinDate()
    {
        if ($this->minDateOption === 'today') {
            $operator = $this->minDateOffset === 'add' ? '+' : '-';
            $interval = "{$operator}{$this->minDateOffsetNumber} {$this->minDateOffsetType}";

            $date = (new DateTime('now', new DateTimeZone('UTC')))->modify($interval);
            $date->setTime(0, 0, 0);

            return $date;
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

            $date = (new DateTime('now', new DateTimeZone('UTC')))->modify($interval);
            $date->setTime(23, 59, 59);

            return $date;
        }

        if ($this->maxDateOption === 'date' && $this->maxDate) {
            return $this->maxDate->setTime(23, 59, 59);
        }

        return null;
    }

    public function getElementValidationRules(): array
    {
        // Keep to disable trait validation on subfields.
        $rules = parent::getElementValidationRules();
        $rules[] = [$this->handle, 'validateDateValues'];

        return $rules;
    }

    public function validateBlocks(ElementInterface $element): void
    {
        // Date fields are a little special when it comes to sub-fields, so validation is quite custom.
        // We override default sub-field validations for "blocks" (the nested sub-fields), and makes
        // it more complicated that a Date field's value is a DateTime.
        $value = $element->getFieldValue($this->fieldKey);
        $scenario = $element->getScenario();

        foreach ($this->getFields() as $field) {
            // No need to validate if the field is conditionally hidden or disabled
            if ($field->isConditionallyHidden($element) || $field->getIsDisabled()) {
                continue;
            }

            // Roll our own validation, due to lack of field layout and elements
            $attribute = 'field:' . $field->getErrorKey();
            $isEmpty = fn() => $field->isValueEmpty($value, $element);

            // Special-handling for date picker/calendar, where the date/time field is required, but it's a single field
            if ($this->displayType === 'calendar' || $this->displayType === 'datePicker') {
                // Strip out `.date` or `.time` from the end of the string
                $attribute = preg_replace('/(\.date|\.time)$/', '', $attribute);
            }

            if ($scenario === Element::SCENARIO_LIVE && $field->required) {
                (new RequiredValidator(['isEmpty' => $isEmpty]))->validateAttribute($element, $attribute);
            }

            foreach ($field->getElementValidationRules() as $rule) {
                $validator = $this->normalizeFieldValidator($attribute, $rule, $field, $element, $isEmpty);

                if (in_array($scenario, $validator->on) || (empty($validator->on) && !in_array($scenario, $validator->except))) {
                    $validator->validateAttributes($element);
                }
            }
        }
    }

    public function validateDateValues(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->fieldKey);

        if (!$value instanceof DateTime) {
            $value = DateTimeHelper::toDateTime($value);
        }

        if (!$value) {
            return;
        }

        if ($min = $this->getMinDate()) {
            if ($value < $min) {
                $element->addError($this->fieldKey, Craft::t('formie', 'Value must be no earlier than {min}.', [
                    'min' => Craft::$app->getFormatter()->asDate($min, Locale::LENGTH_SHORT),
                ]));
            }
        }

        if ($max = $this->getMaxDate()) {
            if ($value > $max) {
                $element->addError($this->fieldKey, Craft::t('formie', 'Value must be no later than {max}.', [
                    'max' => Craft::$app->getFormatter()->asDate($max, Locale::LENGTH_SHORT),
                ]));
            }
        }
    }

    public function getIsDate(): bool
    {
        if ($this->displayType === 'calendar' || $this->displayType === 'datePicker') {
            if ($this->getFieldByHandle('date')?->enabled && !$this->getFieldByHandle('time')?->enabled) {
                return true;
            }
        }

        if ($this->displayType === 'inputs' || $this->displayType === 'dropdowns') {
            if (
                $this->getFieldByHandle('year')?->enabled &&
                $this->getFieldByHandle('month')?->enabled &&
                $this->getFieldByHandle('day')?->enabled &&
                !$this->getFieldByHandle('hour')?->enabled &&
                !$this->getFieldByHandle('minute')?->enabled
            ) {
                return true;
            }
        }

        return false;
    }

    public function getIsTime(): bool
    {
        if ($this->displayType === 'calendar' || $this->displayType === 'datePicker') {
            if (!$this->getFieldByHandle('date')?->enabled && $this->getFieldByHandle('time')?->enabled) {
                return true;
            }
        }

        if ($this->displayType === 'inputs' || $this->displayType === 'dropdowns') {
            if (
                !$this->getFieldByHandle('year')?->enabled &&
                !$this->getFieldByHandle('month')?->enabled &&
                !$this->getFieldByHandle('day')?->enabled &&
                $this->getFieldByHandle('hour')?->enabled &&
                $this->getFieldByHandle('minute')?->enabled
            ) {
                return true;
            }
        }

        return false;
    }

    public function getIsDateTime(): bool
    {
        if ($this->displayType === 'calendar' || $this->displayType === 'datePicker') {
            if ($this->getFieldByHandle('date')?->enabled && $this->getFieldByHandle('time')?->enabled) {
                return true;
            }
        }

        if ($this->displayType === 'inputs' || $this->displayType === 'dropdowns') {
            if (
                $this->getFieldByHandle('year')?->enabled &&
                $this->getFieldByHandle('month')?->enabled &&
                $this->getFieldByHandle('day')?->enabled &&
                $this->getFieldByHandle('hour')?->enabled &&
                $this->getFieldByHandle('minute')?->enabled
            ) {
                return true;
            }
        }

        return false;
    }

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/date/preview', [
            'field' => $this,
        ]);
    }

    public function getDefaultDate(): ?string
    {
        // An alias for `defaultValue` for GQL, as `defaultValue` returns a date, not string
        if ($this->defaultValue instanceof DateTime) {
            return $this->defaultValue->format('Y-m-d\TH:i:s');
        }
        return $this->defaultValue;
    }

    public function getFrontEndJsModules(): ?array
    {
        if ($this->displayType === 'datePicker') {
            // Don't load the date picker in the CP for a few reasons
            // https://github.com/verbb/formie/issues/2595
            if (Craft::$app->getRequest()->getIsCpRequest()) {
                return null;
            }

            $locale = Craft::$app->getLocale()->id;

            // Handle language variants
            if (preg_match('/^([a-z]{2})-/', $locale, $matches)) {
                $locale = $matches[1];
            }

            $supportedLocales = ['ar', 'at', 'az', 'be', 'bg', 'bn', 'cat', 'cs', 'cy', 'da', 'de', 'eo', 'es', 'et', 'fa', 'fi', 'fo', 'fr', 'gr', 'he', 'hi', 'hr', 'hu', 'id', 'is', 'it', 'ja', 'km', 'ko', 'kz', 'lt', 'lv', 'mk', 'mn', 'ms', 'my', 'nl', 'no', 'pa', 'pl', 'pt', 'ro', 'ru', 'si', 'sk', 'sl', 'sq', 'sr-cyr', 'sr', 'sv', 'th', 'tr', 'uk', 'vn', 'zh-tw', 'zh'];

            if (in_array(strtolower($locale), $supportedLocales, true)) {
                $locale = strtolower($locale);
            }

            // When dealing with offsets, ensure that we calculate client-side for caching
            $minDate = null;
            $maxDate = null;

            if ($this->minDateOption === 'today') {
                $operator = $this->minDateOffset === 'add' ? '+' : '-';
                $minDate = "{$operator}{$this->minDateOffsetNumber} {$this->minDateOffsetType}";
            }

            if ($this->minDateOption === 'date' && $this->minDate) {
                $minDate = $this->minDate->setTime(0, 0, 0)->format('Y-m-d H:i:s');
            }

            if ($this->maxDateOption === 'today') {
                $operator = $this->maxDateOffset === 'add' ? '+' : '-';
                $maxDate = "{$operator}{$this->maxDateOffsetNumber} {$this->maxDateOffsetType}";
            }

            if ($this->maxDateOption === 'date' && $this->maxDate) {
                $maxDate = $this->maxDate->setTime(23, 59, 59)->format('Y-m-d H:i:s');
            }

            // Ensure date picker option values are parsed for JSON
            $datePickerOptions = $this->datePickerOptions ?? [];

            foreach ($datePickerOptions as $key => $option) {
                $datePickerOptions[$key]['value'] = Json::decodeIfJson($option['value']);
            }

            // Ensure available days are integers if set
            if (is_array($this->availableDaysOfWeek)) {
                $this->availableDaysOfWeek = array_map('intval', $this->availableDaysOfWeek);
            }

            return [
                'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/fields/date-picker.js'),
                'module' => 'FormieDatePicker',
                'settings' => [
                    'datePickerOptions' => $datePickerOptions,
                    'dateFormat' => $this->getDateFormat(),
                    'timeFormat' => $this->getTimeFormat(),
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

    public function getWeekDayNamesOptions(): array
    {
        $options = [['label' => Craft::t('formie', 'All'), 'value' => '*']];

        foreach (Craft::$app->getLocale()->getWeekDayNames(Locale::LENGTH_FULL) as $key => $value) {
            $options[] = ['label' => $value, 'value' => $key];
        }

        return $options;
    }

    public function beforeSave(bool $isNew): bool
    {
        // Ensure that dates have timezone information stripped off
        if ($this->defaultValue instanceof DateTime) {
            $this->defaultValue = Db::prepareDateForDb($this->defaultValue);
        }

        if ($this->minDate instanceof DateTime) {
            $this->minDate = Db::prepareDateForDb($this->minDate);
        }

        if ($this->maxDate instanceof DateTime) {
            $this->maxDate = Db::prepareDateForDb($this->maxDate);
        }

        // If we've switched display types, we should cleanup field layouts. 
        // Do this before `parent::beforeSave` which will save the layout.
        if ($this->id) {
            if ($savedField = Formie::$plugin->getFields()->getFieldById($this->id)) {
                $displayChanged = $this->displayType !== $savedField->displayType;

                // Calendar and Date Picker layout are the same, no need to delete and remove
                $sameLayoutFamily = in_array($this->displayType, ['calendar', 'datePicker'], true) && in_array($savedField->displayType, ['calendar', 'datePicker'], true);

                if ($displayChanged && !$sameLayoutFamily) {
                    // Delete the saved layout
                    Formie::$plugin->getFields()->deleteLayout($savedField->getFieldLayout());

                    // Create a new field layout, based on the about-to-be-saved layout. This will be stripping
                    // off ID's from the layout and page, but it's the cleanest way to start fresh.
                    $fieldLayout = new FieldLayout();
                    $fieldLayout->setPages([
                        [
                            'label' => Craft::t('formie', 'Page 1'),
                            'settings' => [],
                            'rows' => $this->getFieldLayout()->getRows(),
                        ],
                    ]);

                    $this->setFieldLayout($fieldLayout);

                    $this->nestedLayoutId = null;
                }
            }
        }
        
        return parent::beforeSave($isNew);
    }

    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
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
                'help' => Craft::t('formie', 'Set a default value for the field when it doesn’t have a value.'),
                'name' => 'defaultValue',
                'if' => '$get(defaultOption).value == date',
                'validation' => 'requiredDate',
                'required' => true,
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Display Type'),
                'help' => Craft::t('formie', 'Set different display layouts for this field.'),
                'name' => 'displayType',
                'options' => [
                    ['label' => Craft::t('formie', 'Calendar (Simple)'), 'value' => 'calendar'],
                    ['label' => Craft::t('formie', 'Calendar (Advanced)'), 'value' => 'datePicker'],
                    ['label' => Craft::t('formie', 'Dropdowns'), 'value' => 'dropdowns'],
                    ['label' => Craft::t('formie', 'Text Inputs'), 'value' => 'inputs'],
                ],
            ]),
            SchemaHelper::subFieldsConfigurationField([
                'children' => [
                    [
                        '$cmp' => 'SubFields',
                        'if' => '$get(displayType).value == dropdowns',
                        'props' => [
                            'context' => '$node.context',
                            'type' => static::class,
                            'layoutKey' => 'layouts.dropdowns',
                            'sourceKey' => 'displayType=dropdowns',
                            'sourceValue' => 'displayType',
                        ],
                    ],
                    [
                        '$cmp' => 'SubFields',
                        'if' => '$get(displayType).value == inputs',
                        'props' => [
                            'context' => '$node.context',
                            'type' => static::class,
                            'layoutKey' => 'layouts.inputs',
                            'sourceKey' => 'displayType=inputs',
                            'sourceValue' => 'displayType',
                        ],
                    ],
                    [
                        '$cmp' => 'SubFields',
                        'if' => '$get(displayType).value == calendar || $get(displayType).value == datePicker',
                        'props' => [
                            'context' => '$node.context',
                            'type' => static::class,
                            'layoutKey' => 'layouts.calendar',
                            'sourceKey' => 'displayType=calendar||displayType=datePicker',
                            'sourceValue' => 'displayType',
                        ],
                    ],
                ],
            ]),
        ];
    }

    public function defineSettingsSchema(): array
    {
        return [
            SchemaHelper::prePopulate(),
            SchemaHelper::includeInEmailField(),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Min Date'),
                'help' => Craft::t('formie', 'Set a minimum date for dates to be picked from.'),
                'name' => 'minDateOption',
                'if' => '$get(displayType).value == calendar || $get(displayType).value == datePicker',
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
                'if' => '$get(displayType).value == calendar || $get(displayType).value == datePicker',
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
                'if' => '$get(displayType).value == datePicker',
                'options' => $this->getWeekDayNamesOptions(),
                'showAllOption' => true,
            ]),
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Date Picker Options'),
                'help' => Craft::t('formie', 'Add any additional options for the date picker to use. For available options, refer to the [Flatpickr.js docs](https://flatpickr.js.org/options/).'),
                'name' => 'datePickerOptions',
                'generateValue' => false,
                'validation' => 'min:0',
                'if' => '$get(displayType).value == datePicker',
                'newRowDefaults' => [
                    'label' => '',
                    'value' => '',
                ],
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
            ]),
        ];
    }

    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::subFieldLabelPosition([
                'if' => '$get(displayType).value != calendar && $get(displayType).value != datePicker',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Date Format'),
                'help' => Craft::t('formie', 'Select what format to present dates as.'),
                'name' => 'dateFormat',
                'options' => $this->_getDateFormatOptions(),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Time Format'),
                'help' => Craft::t('formie', 'Select what format to present dates as.'),
                'name' => 'timeFormat',
                'options' => $this->_getTimeFormatOptions(),
            ]),
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
            SchemaHelper::inputAttributesField([
                'if' => '$get(displayType).value == calendar || $get(displayType).value == datePicker',
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

    public function getContentGqlType(): array|Type
    {
        return DateTimeType::getType();
    }

    public function getContentGqlMutationArgumentType(): Type|array
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
                    if ($field->defaultValue instanceof DateTime) {
                        return $field->defaultValue->format('Y-m-d\TH:i:s');
                    }

                    return $field->defaultValue;
                },
            ],
            'displayType' => [
                'name' => 'displayType',
                'type' => Type::string(),
            ],
            'dateFormat' => [
                'name' => 'dateFormat',
                'type' => Type::string(),
            ],
            'timeFormat' => [
                'name' => 'timeFormat',
                'type' => Type::string(),
            ],
            'isDate' => [
                'name' => 'isDate',
                'type' => Type::boolean(),
            ],
            'isTime' => [
                'name' => 'isTime',
                'type' => Type::boolean(),
            ],
            'isDateTime' => [
                'name' => 'isDateTime',
                'type' => Type::boolean(),
            ],
            'defaultDate' => [
                'name' => 'defaultDate',
                'type' => DateTimeType::getType(),
            ],
            'minDate' => [
                'name' => 'minDate',
                'type' => DateTimeType::getType(),
                'resolve' => function($field) {
                    if ($field->minDate instanceof DateTime) {
                        return $field->minDate->format('Y-m-d\TH:i:s');
                    }

                    return $field->minDate;
                },
            ],
            'maxDate' => [
                'name' => 'maxDate',
                'type' => DateTimeType::getType(),
                'resolve' => function($field) {
                    if ($field->maxDate instanceof DateTime) {
                        return $field->maxDate->format('Y-m-d\TH:i:s');
                    }

                    return $field->maxDate;
                },
            ],
            'datePickerOptions' => [
                'name' => 'datePickerOptions',
                'type' => Type::listOf(FieldAttributeGenerator::generateType()),
            ],
            'availableDaysOfWeek' => [
                'name' => 'availableDaysOfWeek',
                'type' => Type::listOf(Type::string()),
                'resolve' => function($field) {
                    $values = [];
                    $options = ArrayHelper::index($field->getWeekDayNamesOptions(), 'value');

                    if (is_array($field->availableDaysOfWeek)) {
                        foreach ($field->availableDaysOfWeek as $number) {
                            $values[] = $options[$number]['label'] ?? null;
                        }
                    }

                    if ($field->availableDaysOfWeek === '*') {
                        foreach ($options as $option) {
                            if ($option['value'] != '*') {
                                $values[] = $option['label'];
                            }
                        }
                    }

                    return $values;
                },
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
        if ($this->displayType !== 'datePicker') {
            if ($key === 'fieldContainer') {
                return new HtmlTag('fieldset', [
                    'class' => 'fui-fieldset fui-subfield-fieldset',
                ]);
            }

            if ($key === 'fieldLabel') {
                $labelPosition = $context['labelPosition'] ?? null;

                return new HtmlTag('legend', [
                    'class' => [
                        'fui-legend',
                    ],
                    'data' => [
                        'field-label' => true,
                        'fui-sr-only' => $labelPosition instanceof HiddenPosition ? true : false,
                    ],
                ]);
            }
        }

        if ($key === 'fieldInput' && $this->displayType === 'datePicker') {
            return new HtmlTag('input', [
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
                    'required-message' => Craft::t('formie', $this->errorMessage) ?: null,
                ],
                'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
            ], $this->getInputAttributes());
        }

        return parent::defineHtmlTag($key, $context);
    }

    // Protected Methods
    // =========================================================================

    protected function defineSubFields(): array
    {
        $fields = [];

        if ($this->displayType == 'datePicker') {
            return $this->getCalendarSubFields();
        }

        if ($this->displayType == 'calendar') {
            return $this->getCalendarSubFields();
        }

        if ($this->displayType == 'dropdowns') {
            return $this->getDropdownSubFields();
        }

        if ($this->displayType == 'inputs') {
            return $this->getInputSubFields();
        }

        return $fields;
    }

    protected function getCalendarSubFields(): array
    {
        $fields = [];

        $fields[0]['fields'][] = [
            'type' => subfields\DateDate::class,
            'label' => Craft::t('formie', 'Date'),
            'handle' => 'date',
            'required' => $this->required,
            'placeholder' => $this->placeholder,
            'errorMessage' => $this->errorMessage,
            'defaultValue' => $this->defaultValue,
            'labelPosition' => HiddenPosition::class,
            'inputAttributes' => array_merge(($this->inputAttributes ?? []), [
                [
                    'label' => 'type',
                    'value' => 'date',
                ],
                [
                    'label' => 'autocomplete',
                    'value' => 'off',
                ],
            ]),
        ];

        $fields[0]['fields'][] = [
            'type' => subfields\DateTime::class,
            'label' => Craft::t('formie', 'Time'),
            'handle' => 'time',
            'required' => $this->required,
            'placeholder' => $this->placeholder,
            'errorMessage' => $this->errorMessage,
            'defaultValue' => $this->defaultValue,
            'labelPosition' => HiddenPosition::class,
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

        return $fields;
    }

    protected function getInputSubFields(): array
    {
        $fields = [];

        // Split the format into an array, so we can only show the fields we need to for dropdowns/inputs
        $format = ($this->getIsDate() ? $this->getDateFormat() : '') . ($this->getIsTime() ? $this->getTimeFormat() : '');
        $format = !$format ? ($this->getDateFormat() . $this->getTimeFormat()) : $format;
        $format = preg_replace('/[.\-:\/ ]/', '', $format);
        $formattingMap = str_split($format);

        // Fix an error in some instances where the defaultValue isn't normalised when saving a form.
        if (!$this->defaultValue instanceof DateTime) {
            $this->defaultValue = DateTimeHelper::toDateTime($this->defaultValue, false, false) ?: null;
        }

        $date = $this->defaultValue ?: new DateTime();
        $year = (int)$date->format('Y');
        $minYear = $year - 100;
        $maxYear = $year + 100;

        $fields[0]['fields'] = [
            [
                'type' => subfields\DateYearNumber::class,
                'label' => Craft::t('formie', 'Year'),
                'handle' => 'year',
                'enabled' => in_array('Y', $formattingMap),
                'labelPosition' => $this->subFieldLabelPosition,
                'limit' => true,
                'min' => $minYear,
                'max' => $maxYear,
            ],
            [
                'type' => subfields\DateMonthNumber::class,
                'label' => Craft::t('formie', 'Month'),
                'handle' => 'month',
                'enabled' => in_array('m', $formattingMap),
                'labelPosition' => $this->subFieldLabelPosition,
                'limit' => true,
                'min' => 1,
                'max' => 12,
            ],
            [
                'type' => subfields\DateDayNumber::class,
                'label' => Craft::t('formie', 'Day'),
                'handle' => 'day',
                'enabled' => in_array('d', $formattingMap),
                'labelPosition' => $this->subFieldLabelPosition,
                'limit' => true,
                'min' => 1,
                'max' => 31,
            ],
            [
                'type' => subfields\DateHourNumber::class,
                'label' => Craft::t('formie', 'Hour'),
                'handle' => 'hour',
                'enabled' => in_array('H', $formattingMap) || in_array('h', $formattingMap),
                'labelPosition' => $this->subFieldLabelPosition,
                'limit' => true,
                'min' => 0,
                'max' => 23,
            ],
            [
                'type' => subfields\DateMinuteNumber::class,
                'label' => Craft::t('formie', 'Minute'),
                'handle' => 'minute',
                'enabled' => in_array('i', $formattingMap),
                'labelPosition' => $this->subFieldLabelPosition,
                'limit' => true,
                'min' => 0,
                'max' => 59,
            ],
            [
                'type' => subfields\DateSecondNumber::class,
                'label' => Craft::t('formie', 'Second'),
                'handle' => 'second',
                'enabled' => in_array('s', $formattingMap),
                'labelPosition' => $this->subFieldLabelPosition,
                'limit' => true,
                'min' => 0,
                'max' => 59,
            ],
            [
                'type' => subfields\DateAmPmDropdown::class,
                'label' => Craft::t('formie', 'AM/PM'),
                'handle' => 'ampm',
                'enabled' => in_array('A', $formattingMap),
                'labelPosition' => $this->subFieldLabelPosition,
                'options' => [
                    ['value' => 'AM', 'label' => Craft::t('formie', 'AM')],
                    ['value' => 'PM', 'label' => Craft::t('formie', 'PM')],
                ],
            ],
        ];

        return $fields;
    }

    protected function getDropdownSubFields(): array
    {
        $fields = [];

        // Split the format into an array, so we can only show the fields we need to for dropdowns/inputs
        $format = ($this->getIsDate() ? $this->getDateFormat() : '') . ($this->getIsTime() ? $this->getTimeFormat() : '');
        $format = !$format ? ($this->getDateFormat() . $this->getTimeFormat()) : $format;
        $format = preg_replace('/[.\-:\/ ]/', '', $format);
        $formattingMap = str_split($format);

        $minYear = $this->_getYearOptions()[1]['value'];
        $maxYear = $this->_getYearOptions()[count($this->_getYearOptions()) - 1]['value'];

        $fields[0]['fields'] = [
            [
                'type' => subfields\DateYearDropdown::class,
                'label' => Craft::t('formie', 'Year'),
                'handle' => 'year',
                'enabled' => in_array('Y', $formattingMap),
                'labelPosition' => $this->subFieldLabelPosition,
                'options' => $this->_getYearOptions(),
            ],
            [
                'type' => subfields\DateMonthDropdown::class,
                'label' => Craft::t('formie', 'Month'),
                'handle' => 'month',
                'enabled' => in_array('m', $formattingMap),
                'labelPosition' => $this->subFieldLabelPosition,
                'options' => $this->_getMonthOptions(),
            ],
            [
                'type' => subfields\DateDayDropdown::class,
                'label' => Craft::t('formie', 'Day'),
                'handle' => 'day',
                'enabled' => in_array('d', $formattingMap),
                'labelPosition' => $this->subFieldLabelPosition,
                'options' => $this->_generateOptions(1, 31),
            ],
            [
                'type' => subfields\DateHourDropdown::class,
                'label' => Craft::t('formie', 'Hour'),
                'handle' => 'hour',
                'enabled' => in_array('H', $formattingMap) || in_array('h', $formattingMap),
                'labelPosition' => $this->subFieldLabelPosition,
                'options' => $this->_generateOptions(0, 23),
            ],
            [
                'type' => subfields\DateMinuteDropdown::class,
                'label' => Craft::t('formie', 'Minute'),
                'handle' => 'minute',
                'enabled' => in_array('i', $formattingMap),
                'labelPosition' => $this->subFieldLabelPosition,
                'options' => $this->_generateOptions(0, 59),
            ],
            [
                'type' => subfields\DateSecondDropdown::class,
                'label' => Craft::t('formie', 'Second'),
                'handle' => 'second',
                'enabled' => in_array('s', $formattingMap),
                'labelPosition' => $this->subFieldLabelPosition,
                'options' => $this->_generateOptions(0, 59),
            ],
            [
                'type' => subfields\DateAmPmDropdown::class,
                'label' => Craft::t('formie', 'AM/PM'),
                'handle' => 'ampm',
                'enabled' => in_array('A', $formattingMap),
                'labelPosition' => $this->subFieldLabelPosition,
                'options' => [
                    ['value' => 'AM', 'label' => Craft::t('formie', 'AM')],
                    ['value' => 'PM', 'label' => Craft::t('formie', 'PM')],
                ],
            ],
        ];

        return $fields;
    }

    protected function cpInputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/date/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'element' => $element,
        ]);
    }

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
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

    protected function defineValueAsJson(mixed $value, ElementInterface $element = null): mixed
    {
        return $this->getValueAsString($value, $element);
    }

    protected function defineValueForExport(mixed $value, ElementInterface $element = null): mixed
    {
        return $this->getValueAsString($value, $element);
    }

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): string
    {
        return $this->getValueAsString($value, $element);
    }

    protected function defineValueForIntegration(mixed $value, IntegrationField $integrationField, IntegrationInterface $integration, ElementInterface $element = null, string $fieldKey = ''): mixed
    {
        // If a string value is requested for a date, return the ISO 8601 date string
        if ($integrationField->getType() === IntegrationField::TYPE_STRING) {
            $format = 'c';

            // Check if we're mapping sub-fields
            if ($fieldKey === 'year') {
                $format = 'Y';
            } else if ($fieldKey === 'month') {
                $format = 'm';
            } else if ($fieldKey === 'day') {
                $format = 'd';
            } else if ($fieldKey === 'hour') {
                $format = 'H';
            } else if ($fieldKey === 'minute') {
                $format = 'i';
            } else if ($fieldKey === 'second') {
                $format = 's';
            } else if ($fieldKey === 'ampm') {
                $format = 'A';
            }

            if (!$this->getIsTime()) {
                if ($value instanceof DateTime) {
                    return $value->format($format);
                }

                return $value;
            }
        }

        if ($integrationField->getType() === IntegrationField::TYPE_DATE) {
            if ($date = DateTimeHelper::toDateTime($value)) {
                return $date->format('Y-m-d');
            }
        }

        if ($integrationField->getType() === IntegrationField::TYPE_DATETIME) {
            if ($date = DateTimeHelper::toDateTime($value)) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        if ($integrationField->getType() === IntegrationField::TYPE_DATECLASS) {
            if ($date = DateTimeHelper::toDateTime($value)) {
                return $date;
            }
        }

        // Fetch the default handling
        return parent::defineValueForIntegration($value, $integrationField, $integration, $element);
    }

    protected function defineValueForEmailPreview(FakerFactory $faker): mixed
    {
        return $faker->dateTime();
    }

    protected function defineValueForVariable(mixed $value, Submission $submission, Notification $notification): mixed
    {
        $props = [
            'year' => 'Y',
            'month' => 'm',
            'day' => 'd',
            'hour' => 'H',
            'minute' => 'i',
            'second' => 's',
            'ampm' => 'a',
        ];

        $values = [];

        if ($value) {
            if ($this->displayType === 'inputs' || $this->displayType === 'dropdowns') {
                foreach ($props as $k => $format) {
                    $formattedValue = '';

                    if ($value && $value instanceof DateTime) {
                        $formattedValue = $value->format($format);
                    }

                    $values[$k] = $formattedValue;
                }
            } else {
                $values = [
                    '__toString' => $this->defineValueAsString($value),
                    'date' => $value->format($this->getDateFormat()),
                    'time' => $value->format($this->getTimeFormat()),
                ];
            }
        }

        return $values;
    }

    // Private Methods
    // =========================================================================

    private function _generateOptions(int $start, int $end, ?string $placeholder = null): array
    {
        $options = [['value' => '', 'label' => $placeholder, 'disabled' => true]];

        for ($i = $start; $i <= $end; $i++) {
            $options[] = ['label' => $i, 'value' => $i];
        }

        return $options;
    }

    private function _getMonthOptions(?string $placeholder = null): array
    {
        $options = [['value' => '', 'label' => $placeholder, 'disabled' => true]];

        foreach (Craft::$app->getLocale()->getMonthNames() as $index => $monthName) {
            $options[] = ['value' => $index + 1, 'label' => $monthName];
        }

        return $options;
    }

    private function _getYearOptions(?string $placeholder = null): array
    {
        // Handle case when defaultValue is a string or null
        $defaultValue = $this->defaultValue;
        if (is_string($defaultValue)) {
            try {
                $defaultValue = new DateTime($defaultValue);
            } catch (Exception $e) {
                $defaultValue = new DateTime();
            }
        } elseif (!$defaultValue instanceof DateTime) {
            $defaultValue = new DateTime();
        }

        $year = (int)$defaultValue->format('Y');
        $minYear = $year + $this->minYearRange;
        $maxYear = $year + $this->maxYearRange;

        $options = [['value' => '', 'label' => $placeholder, 'disabled' => true]];

        for ($y = $minYear; $y <= $maxYear; $y++) {
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

        $event = new RegisterDateTimeFormatOptionsEvent([
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

        $event = new RegisterDateTimeFormatOptionsEvent([
            'field' => $this,
            'options' => $options,
        ]);
        $this->trigger(self::EVENT_REGISTER_TIME_FORMAT_OPTIONS, $event);

        return $event->options;
    }

    private function _isDateTimeString(mixed $value): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }

        // Very simple "YYYY-MM-DD HH:MM[:SS]" or "YYYY-MM-DDTHH:MM[:SS]" check
        return (bool)preg_match(
            '/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(?::\d{2})?$/',
            $value
        );
    }
}
