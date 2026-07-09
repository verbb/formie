<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\SortableFieldInterface;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\FixedParentFieldInterface;
use verbb\formie\base\FixedParentField;
use verbb\formie\base\PreviewableFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyDateTimeFormatEvent;
use verbb\formie\events\ModifyFieldValueEvent;
use verbb\formie\events\RegisterDateTimeFormatOptionsEvent;
use verbb\formie\fields\values\DateFieldValue;
use verbb\formie\fields\values\DateRangeFieldValue;
use verbb\formie\fields\definitions\FieldClientModules;
use verbb\formie\fields\definitions\FieldReferenceValue;
use verbb\formie\fields\definitions\FieldValueClass;
use verbb\formie\fields\values\OptionValue;
use verbb\formie\fields\values\SingleOptionFieldValue;
use verbb\formie\fields\subfields\DateYear;
use verbb\formie\gql\types\generators\FieldAttributeGenerator;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\ValidationMessagesHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\ClientModule;
use verbb\formie\models\SlotTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;
use verbb\formie\positions\Hidden as HiddenPosition;

use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\DateTime as DateTimeType;
use craft\helpers\Component;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\i18n\Locale;

use Faker\Generator as FakerFactory;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

use yii\base\Event;
use yii\db\ExpressionInterface;
use yii\db\Schema;
use yii\validators\RequiredValidator;
use yii\validators\Validator;

use DateTime;
use DateTimeZone;

class Date extends FixedParentField implements SortableFieldInterface, PreviewableFieldInterface
{
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

    public static function supportsGqlConfigProvider(): bool
    {
        return true;
    }

    public static function gqlContentTypeFromConfig(array $config): Type|array
    {
        if (self::_configCollectsRange($config)) {
            return self::_gqlDateRangeTypeFromConfig($config);
        }

        return DateTimeType::getType();
    }

    public static function gqlContentMutationArgumentTypeFromConfig(array $config): Type|array
    {
        if (self::_configCollectsRange($config)) {
            return [
                'name' => $config['handle'] ?? '',
                'type' => self::_gqlDateRangeInputTypeFromConfig($config),
                'description' => $config['instructions'] ?? null,
            ];
        }

        return [
            'name' => $config['handle'] ?? '',
            'type' => DateTimeType::getType(),
            'description' => $config['instructions'] ?? null,
        ];
    }

    public function themeConfigKey(): string
    {
        return 'dateTime';
    }

    public static function toDateTime($value): DateTime|bool
    {
        // We should never deal with timezones
        return DateTimeHelper::toDateTime($value, false, false);
    }

    public static function dbType(): string
    {
        return Schema::TYPE_JSON;
    }

    public static function queryCondition(array $instances, mixed $value, array &$params): array|string|ExpressionInterface|false|null
    {
        if (is_array($value) && array_key_exists('value', $value)) {
            $value = $value['value'];
        }

        if (is_array($value) && array_key_exists('datetime', $value)) {
            $value = $value['datetime'];
        }

        if ($value === ':empty:' || $value === ':notempty:') {
            $partKeys = ['year', 'month', 'day', 'hour', 'minute', 'second', 'ampm'];
            $conditions = $value === ':empty:' ? ['and'] : ['or'];

            foreach ($partKeys as $partKey) {
                $partSql = self::_valueSqlForPart($instances, $partKey);

                if ($partSql === null) {
                    continue;
                }

                if ($value === ':empty:') {
                    $conditions[] = ['or', [$partSql => null], [$partSql => '']];
                } else {
                    $conditions[] = ['not', [$partSql => null]];
                }
            }

            return count($conditions) > 1 ? $conditions : false;
        }

        if (is_array($value)) {
            $knownPartKeys = ['year', 'month', 'day', 'hour', 'minute', 'second', 'ampm'];
            $partCriteria = array_intersect_key($value, array_flip($knownPartKeys));

            if (!empty($partCriteria)) {
                $partConditions = ['and'];

                foreach ($partCriteria as $partKey => $partValue) {
                    $partSql = self::_valueSqlForPart($instances, $partKey);

                    if ($partSql === null) {
                        continue;
                    }

                    $columnType = $partKey === 'ampm' ? Schema::TYPE_STRING : Schema::TYPE_INTEGER;
                    $partConditions[] = Db::parseParam($partSql, $partValue, columnType: $columnType);
                }

                return count($partConditions) > 1 ? $partConditions : false;
            }
        }

        $comparableSql = self::_valueSqlForComparable($instances);

        if ($comparableSql === null) {
            return false;
        }

        $normalizedComparableValue = self::_normalizeComparableQueryValue($value);

        if ($normalizedComparableValue === null) {
            return false;
        }

        return Db::parseParam($comparableSql, $normalizedComparableValue, columnType: Schema::TYPE_STRING);
    }


    // Constants
    // =========================================================================

    public const EVENT_MODIFY_DATE_FORMAT = 'modifyDateFormat';
    public const EVENT_MODIFY_TIME_FORMAT = 'modifyTimeFormat';
    public const EVENT_REGISTER_DATE_FORMAT_OPTIONS = 'registerDateFormatOptions';
    public const EVENT_REGISTER_TIME_FORMAT_OPTIONS = 'registerTimeFormatOptions';
    
    public const COLLECT_SINGLE = 'single';
    public const COLLECT_RANGE = 'range';


    // Properties
    // =========================================================================

    public string $dateFormat = 'Y-m-d';
    public string $timeFormat = 'H:i';
    public string $collectMode = self::COLLECT_SINGLE;
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
    public mixed $availableDaysOfWeek = '*';
    public array $layouts = [];


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
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
                // Resolved dynamically in getDefaultValue() so "today" reflects the
                // current request and respects whether the field collects time.
                $config['defaultValue'] = null;
            } else {
                $config['defaultValue'] = null;
            }
        } else {
            $config['defaultValue'] = null;
        }

        if (($config['defaultOption'] ?? null) === 'today') {
            // Subfield defaultValue rows are editor scaffolding only; "today" resolves dynamically.
            if (isset($config['rows']) && is_array($config['rows'])) {
                self::_clearSubFieldDefaultValues($config['rows']);
            }

            foreach ($config['layouts'] ?? [] as &$layoutRows) {
                if (is_array($layoutRows)) {
                    self::_clearSubFieldDefaultValues($layoutRows);
                }
            }
            unset($layoutRows);
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

    public function fieldKind(): string
    {
        return self::KIND_DATE;
    }

    public function getSortOption(): array
    {
        // Date values are stored as JSON parts; use the comparable key so CP sorting is chronological.
        $comparableSql = self::_valueSqlForComparable([$this]);

        if ($comparableSql === null) {
            return parent::getSortOption();
        }

        return [
            'label' => $this->label,
            'orderBy' => [$comparableSql, 'elements.id'],
            'attribute' => "field:{$this->uid}",
        ];
    }

    public function getCollectsRange(): bool
    {
        return $this->collectMode === self::COLLECT_RANGE && $this->displayType === 'datePicker';
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        if ($this->getCollectsRange()) {
            $rules[] = [$this->handle, 'validateCollectRange', 'skipOnEmpty' => false];
        }

        if (in_array($this->displayType, ['inputs', 'dropdowns'], true)) {
            $rules[] = [$this->handle, 'validateDateParts', 'skipOnEmpty' => false];
        }

        return $rules;
    }

    public function validateDateParts(ElementInterface $element): void
    {
        if (!in_array($this->displayType, ['inputs', 'dropdowns'], true)) {
            return;
        }

        $parts = $this->_collectEnabledDatePartsFromElement($element);

        if (!$this->_hasAnyDatePartValue($parts)) {
            return;
        }

        if ($this->getIsDate() || $this->getIsDateTime()) {
            if (!$this->_hasAllEnabledDateParts($parts)) {
                return;
            }

            if (!DateFieldValue::isValidCalendarDate($parts)) {
                $partField = $this->getFieldByHandle('day');

                if ($partField instanceof Field) {
                    $element->addError($partField->valueKey(), $partField->getValidationMessage(ValidationMessagesHelper::KEY_INVALID));
                } else {
                    $element->addError($this->valueKey(), $this->getValidationMessage(ValidationMessagesHelper::KEY_INVALID));
                }

                return;
            }
        }

        $dateTime = DateFieldValue::partsToDateTime($parts);

        if (!$dateTime instanceof DateTime) {
            return;
        }

        $minDate = $this->getMinDate();

        if ($minDate instanceof DateTime && $dateTime < $minDate) {
            $element->addError($this->valueKey(), Craft::t('formie', 'The date must be on or after {date}.', [
                'date' => Craft::$app->getFormatter()->asDate($minDate),
            ]));

            return;
        }

        $maxDate = $this->getMaxDate();

        if ($maxDate instanceof DateTime && $dateTime > $maxDate) {
            $element->addError($this->valueKey(), Craft::t('formie', 'The date must be on or before {date}.', [
                'date' => Craft::$app->getFormatter()->asDate($maxDate),
            ]));
        }
    }

    public function validateCollectRange(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->valueKey());

        if (!$value instanceof DateRangeFieldValue) {
            return;
        }

        $start = DateFieldValue::partsToDateTime($value->getStartParts());
        $end = DateFieldValue::partsToDateTime($value->getEndParts());

        if (!$start || !$end || $end >= $start) {
            return;
        }

        $element->addError($this->valueKey(), Craft::t('formie', 'The end date must be after the start date.'));
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

    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'layouts';

        return array_values(array_unique($attributes));
    }

    public function getFormBuilderSettings(): array
    {
        $settings = parent::getFormBuilderSettings();
        $defaultLayouts = [
            'calendar' => $this->getCalendarSubFields(),
            'calendarRange' => $this->getRangeCalendarSubFields(),
            'dropdowns' => $this->getDropdownSubFields(),
            'inputs' => $this->getInputSubFields(),
        ];
        $persistedLayouts = is_array($this->layouts) ? $this->layouts : [];
        $activeLayoutKey = $this->_getActiveLayoutKey();
        $activeRows = $settings['rows'] ?? [];
        $layouts = [];

        foreach ($defaultLayouts as $layoutKey => $rows) {
            $layoutRows = $persistedLayouts[$layoutKey] ?? null;

            if ($layoutKey === $activeLayoutKey && is_array($activeRows) && $activeRows) {
                $layoutRows = $activeRows;
            }

            $normalizedRows = $this->normalizeSubFieldRows(
                is_array($layoutRows) ? $layoutRows : [],
                $rows,
            );

            $layouts[$layoutKey] = $normalizedRows ?: $rows;
        }

        $settings['layouts'] = $layouts;
        $settings['rows'] = $layouts[$activeLayoutKey] ?? $this->normalizeSubFieldRows(
            is_array($activeRows) ? $activeRows : [],
            $defaultLayouts[$activeLayoutKey] ?? [],
        );

        // Builder rows are flat field configs; normalize sub-field defaults to scalar preview values.
        if (isset($settings['rows']) && is_array($settings['rows'])) {
            $this->_sanitizeSubFieldRowsForBuilder($settings['rows']);
        }

        foreach ($settings['layouts'] ?? [] as &$layoutRows) {
            if (is_array($layoutRows)) {
                $this->_sanitizeSubFieldRowsForBuilder($layoutRows);
            }
        }
        unset($layoutRows);

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

    public function getEffectivePlaceholder(): ?string
    {
        if (trim((string)$this->placeholder) !== '') {
            return $this->placeholder ?: null;
        }

        if ($this->displayType !== 'datePicker' || $this->getCollectsRange()) {
            return null;
        }

        return $this->_getFormatPlaceholderLabel();
    }

    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        if (!$value) {
            return $this->renderPreviewText('');
        }

        if ($this->getCollectsRange()) {
            return $this->renderPreviewText($this->formatRangeValueForDisplay($value));
        }

        // Render from stored date parts so CP previews stay wall-clock and avoid Craft formatter timezone shifts.
        return $this->renderPreviewText($this->getValueAsString($value, $element));
    }

    public function dateTimeFromValue(mixed $value): ?\DateTime
    {
        $parts = $value instanceof DateFieldValue
            ? $value->getParts()
            : DateFieldValue::parseParts($value);

        return DateFieldValue::partsToDateTime($parts);
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($this->getCollectsRange()) {
            if ($value instanceof DateRangeFieldValue) {
                if ($value->isEmpty()) {
                    return null;
                }

                $this->_applyDisplaySettings($value);

                return $value;
            }

            if ($value === null || $value === '') {
                return null;
            }

            $normalized = DateRangeFieldValue::fromMixed($this->_normalizeInputValue($value));

            if ($normalized->isEmpty()) {
                return null;
            }

            $this->_applyDisplaySettings($normalized);

            return $normalized;
        }

        if ($value instanceof DateFieldValue) {
            if ($value->isEmpty()) {
                return null;
            }

            $this->_applyDisplaySettings($value);

            return $value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        $value = $this->_normalizeInputValue($value);
        $normalized = new DateFieldValue($value);

        if ($normalized->isEmpty()) {
            return null;
        }

        $this->_applyDisplaySettings($normalized);

        return $normalized;
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($this->getCollectsRange()) {
            if ($value instanceof DateRangeFieldValue) {
                if ($value->isEmpty()) {
                    return null;
                }

                return [
                    'start' => $value->getStartParts(),
                    'end' => $value->getEndParts(),
                ];
            }

            $value = $this->_normalizeInputValue($value);
            $normalized = DateRangeFieldValue::fromMixed($value);

            if ($normalized->isEmpty()) {
                return null;
            }

            return [
                'start' => $normalized->getStartParts(),
                'end' => $normalized->getEndParts(),
            ];
        }

        if ($value instanceof DateFieldValue) {
            $parts = $value->getParts();

            if (empty($parts)) {
                return null;
            }

            return $parts;
        }

        $value = $this->_normalizeInputValue($value);
        $parts = DateFieldValue::parseParts($value);

        return empty($parts) ? null : $parts;
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

    public function getIsDate(): bool
    {
        if ($this->getCollectsRange()) {
            if ($this->displayType === 'calendar' || $this->displayType === 'datePicker') {
                return $this->getFieldByHandle('startDate')?->enabled
                    && !$this->getFieldByHandle('startTime')?->enabled;
            }

            if ($this->displayType === 'inputs' || $this->displayType === 'dropdowns') {
                return $this->getFieldByHandle('startYear')?->enabled
                    && $this->getFieldByHandle('startMonth')?->enabled
                    && $this->getFieldByHandle('startDay')?->enabled
                    && !$this->getFieldByHandle('startHour')?->enabled
                    && !$this->getFieldByHandle('startMinute')?->enabled;
            }
        }

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
        if ($this->getCollectsRange()) {
            if ($this->displayType === 'calendar' || $this->displayType === 'datePicker') {
                return !$this->getFieldByHandle('startDate')?->enabled
                    && $this->getFieldByHandle('startTime')?->enabled;
            }

            if ($this->displayType === 'inputs' || $this->displayType === 'dropdowns') {
                return !$this->getFieldByHandle('startYear')?->enabled
                    && !$this->getFieldByHandle('startMonth')?->enabled
                    && !$this->getFieldByHandle('startDay')?->enabled
                    && $this->getFieldByHandle('startHour')?->enabled
                    && $this->getFieldByHandle('startMinute')?->enabled;
            }
        }

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
        if ($this->getCollectsRange()) {
            if ($this->displayType === 'calendar' || $this->displayType === 'datePicker') {
                return $this->getFieldByHandle('startDate')?->enabled
                    && $this->getFieldByHandle('startTime')?->enabled;
            }

            if ($this->displayType === 'inputs' || $this->displayType === 'dropdowns') {
                return $this->getFieldByHandle('startYear')?->enabled
                    && $this->getFieldByHandle('startMonth')?->enabled
                    && $this->getFieldByHandle('startDay')?->enabled
                    && $this->getFieldByHandle('startHour')?->enabled
                    && $this->getFieldByHandle('startMinute')?->enabled;
            }
        }

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

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewContainerParent(),
        ];
    }

    public function getDefaultDate(): ?string
    {
        // An alias for `defaultValue` for GQL, as `defaultValue` returns a date, not string
        $defaultValue = $this->getDefaultValue();

        if ($defaultValue instanceof DateTime) {
            return $defaultValue->format('Y-m-d\TH:i:s');
        }

        if ($defaultValue instanceof DateFieldValue) {
            $dateTime = DateFieldValue::toDateTime($defaultValue);

            return $dateTime?->format('Y-m-d\TH:i:s');
        }

        return is_string($defaultValue) ? $defaultValue : null;
    }

    public function getDefaultValue(): mixed
    {
        if ($this->defaultOption === 'today') {
            $defaultValue = $this->normalizeValue($this->_resolveTodayDefaultDateTime(), null);

            $event = new ModifyFieldValueEvent([
                'value' => $defaultValue,
                'field' => $this,
            ]);

            $this->trigger(static::EVENT_MODIFY_DEFAULT_VALUE, $event);

            return $event->value;
        }

        return parent::getDefaultValue();
    }

    public function getSubFieldPartValue(mixed $value, string $handle): mixed
    {
        if ($value instanceof OptionValue || $value instanceof SingleOptionFieldValue) {
            return $value->value;
        }

        return $this->resolveNormalizedValuePath($value, $handle);
    }

    public function resolveNormalizedValuePath(mixed $value, string $path): mixed
    {
        if ($this->getCollectsRange()) {
            $rangeValue = $value instanceof DateRangeFieldValue
                ? $value
                : DateRangeFieldValue::fromMixed($value);

            $this->_applyDisplaySettings($rangeValue);

            return $rangeValue->getPathValue($path);
        }

        $fieldValue = $value instanceof DateFieldValue
            ? $value
            : new DateFieldValue(DateFieldValue::parseParts($value));

        $this->_applyDisplaySettings($fieldValue);

        return $fieldValue->getPathValue($path);
    }

    public function formatPartsForDisplay(array $parts): string
    {
        $fieldValue = new DateFieldValue($parts);
        $this->_applyDisplaySettings($fieldValue);

        return $fieldValue->formatPartsForDisplay($parts);
    }

    public function formatDatePartForDisplay(array $parts): string
    {
        $fieldValue = new DateFieldValue($parts);
        $this->_applyDisplaySettings($fieldValue);

        return $fieldValue->formatDateForDisplay($parts);
    }

    public function formatTimePartForDisplay(array $parts): string
    {
        $fieldValue = new DateFieldValue($parts);
        $this->_applyDisplaySettings($fieldValue);

        return $fieldValue->formatTimeForDisplay($parts);
    }

    public function formatRangeValueForDisplay(mixed $value): string
    {
        $rangeValue = $value instanceof DateRangeFieldValue
            ? $value
            : DateRangeFieldValue::fromMixed($value);

        if (!$value instanceof DateRangeFieldValue) {
            $this->_applyDisplaySettings($rangeValue);
        }

        if ($rangeValue->isEmpty()) {
            return '';
        }

        return $rangeValue->formatForDisplay();
    }

    public function getRangeBoundaryInputValue(mixed $value, string $side): string
    {
        if (!$this->getCollectsRange()) {
            return '';
        }

        $rangeValue = $value instanceof DateRangeFieldValue
            ? $value
            : DateRangeFieldValue::fromMixed($value);

        if ($rangeValue->isEmpty()) {
            return '';
        }

        $parts = $side === 'end' ? $rangeValue->getEndParts() : $rangeValue->getStartParts();
        $dateTime = DateFieldValue::partsToDateTime($parts);

        if (!$dateTime instanceof \DateTime) {
            return '';
        }

        if ($this->getIsDateTime() || $this->getIsTime()) {
            return $dateTime->format('Y-m-d H:i:s');
        }

        return $dateTime->format('Y-m-d');
    }

    public function getWeekDayNamesOptions(): array
    {
        $options = [];

        foreach (Craft::$app->getLocale()->getWeekDayNames(Locale::LENGTH_FULL) as $key => $value) {
            $options[] = ['label' => $value, 'value' => $key];
        }

        return $options;
    }

    public function beforeSave(bool $isNew): bool
    {
        if ($this->collectMode === self::COLLECT_RANGE && $this->displayType !== 'datePicker') {
            $this->collectMode = self::COLLECT_SINGLE;
        }

        $this->_syncRowsFromActiveLayout();

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

        return parent::beforeSave($isNew);
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Display Type'),
                'instructions' => Craft::t('formie', 'Set different display layouts for this field.'),
                'name' => 'displayType',
                'options' => [
                    ['label' => Craft::t('formie', 'Calendar (Simple)'), 'value' => 'calendar'],
                    ['label' => Craft::t('formie', 'Calendar (Advanced)'), 'value' => 'datePicker'],
                    ['label' => Craft::t('formie', 'Dropdowns'), 'value' => 'dropdowns'],
                    ['label' => Craft::t('formie', 'Text Inputs'), 'value' => 'inputs'],
                ],
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Value Type'),
                'instructions' => Craft::t('formie', 'Choose whether to collect a single date/time or a start/end range.'),
                'name' => 'collectMode',
                'if' => 'displayType == "datePicker"',
                'options' => [
                    ['label' => Craft::t('formie', 'Single Date/Time'), 'value' => self::COLLECT_SINGLE],
                    ['label' => Craft::t('formie', 'Date Range'), 'value' => self::COLLECT_RANGE],
                ],
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Default Value'),
                'instructions' => Craft::t('formie', 'Select a default value for this field.'),
                'name' => 'defaultOption',
                'if' => 'displayType != "datePicker" || collectMode == "' . self::COLLECT_SINGLE . '"',
                'options' => [
                    ['label' => Craft::t('formie', 'None'), 'value' => ''],
                    ['label' => Craft::t('formie', 'Today‘s Date/Time'), 'value' => 'today'],
                    ['label' => Craft::t('formie', 'Specific Date/Time'), 'value' => 'date'],
                ],
            ]),
            SchemaHelper::dateField([
                'label' => Craft::t('formie', 'Default Date/Time'),
                'instructions' => Craft::t('formie', 'Set a default value for the field when it doesn’t have a value.'),
                'name' => 'defaultValue',
                'if' => '(displayType != "datePicker" || collectMode == "' . self::COLLECT_SINGLE . '") && defaultOption == "date"',
                'validation' => 'requiredDate',
                'required' => true,
            ]),
            SchemaHelper::nestedFieldsConfigurationField([
                'label' => Craft::t('formie', 'Sub-Field Configuration'),
                'instructions' => Craft::t('formie', 'Configure the sub-fields for this field. Move to rearrange columns and rows, and click to edit sub-field settings.'),
                'children' => [
                    [
                        '$cmp' => 'NestedLayout',
                        'if' => 'displayType == "dropdowns"',
                        'props' => [
                            'parentType' => static::class,
                            'layoutKey' => 'layouts.dropdowns',
                        ],
                    ],
                    [
                        '$cmp' => 'NestedLayout',
                        'if' => 'displayType == "inputs"',
                        'props' => [
                            'parentType' => static::class,
                            'layoutKey' => 'layouts.inputs',
                        ],
                    ],
                    [
                        '$cmp' => 'NestedLayout',
                        'if' => 'displayType == "datePicker" && collectMode == "' . self::COLLECT_RANGE . '"',
                        'props' => [
                            'parentType' => static::class,
                            'layoutKey' => 'layouts.calendarRange',
                        ],
                    ],
                    [
                        '$cmp' => 'NestedLayout',
                        'if' => 'displayType == "calendar" || (displayType == "datePicker" && collectMode != "' . self::COLLECT_RANGE . '")',
                        'props' => [
                            'parentType' => static::class,
                            'layoutKey' => 'layouts.calendar',
                        ],
                    ],
                ],
            ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::prePopulate(),
            SchemaHelper::includeInEmailFieldSummariesField(),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Min Date'),
                'instructions' => Craft::t('formie', 'Set a minimum date for dates to be picked from.'),
                'name' => 'minDateOption',
                'if' => 'displayType == "calendar" || displayType == "datePicker" || displayType == "inputs" || displayType == "dropdowns"',
                'options' => [
                    ['label' => Craft::t('formie', 'None'), 'value' => ''],
                    ['label' => Craft::t('formie', 'Today‘s Date/Time'), 'value' => 'today'],
                    ['label' => Craft::t('formie', 'Specific Date/Time'), 'value' => 'date'],
                ],
            ]),
            SchemaHelper::dateField([
                'label' => Craft::t('formie', 'Min Date'),
                'instructions' => Craft::t('formie', 'Set a minimum date for dates to be picked from.'),
                'name' => 'minDate',
                'if' => 'minDateOption == "date"',
            ]),
            SchemaHelper::fieldWrap([
                'label' => Craft::t('formie', 'Offset'),
                'instructions' => Craft::t('formie', 'Enter an optional offset for today‘s date.'),
                'if' => 'minDateOption == "today"',
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
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Max Date'),
                'instructions' => Craft::t('formie', 'Set a maximum date for dates to be picked up to.'),
                'name' => 'maxDateOption',
                'if' => 'displayType == "calendar" || displayType == "datePicker" || displayType == "inputs" || displayType == "dropdowns"',
                'options' => [
                    ['label' => Craft::t('formie', 'None'), 'value' => ''],
                    ['label' => Craft::t('formie', 'Today‘s Date/Time'), 'value' => 'today'],
                    ['label' => Craft::t('formie', 'Specific Date/Time'), 'value' => 'date'],
                ],
            ]),
            SchemaHelper::dateField([
                'label' => Craft::t('formie', 'Max Date'),
                'instructions' => Craft::t('formie', 'Set a maximum date for dates to be picked up to.'),
                'name' => 'maxDate',
                'if' => 'maxDateOption == "date"',
            ]),
            SchemaHelper::fieldWrap([
                'label' => Craft::t('formie', 'Offset'),
                'instructions' => Craft::t('formie', 'Enter an optional offset for today‘s date.'),
                'if' => 'maxDateOption == "today"',
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
            ]),
            SchemaHelper::checkboxSelectField([
                'label' => Craft::t('formie', 'Available Days'),
                'instructions' => Craft::t('formie', 'Choose which days of the week should be available.'),
                'name' => 'availableDaysOfWeek',
                'if' => 'displayType == "datePicker"',
                'options' => $this->getWeekDayNamesOptions(),
                'showAllOption' => true,
            ]),
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Date Picker Options'),
                'instructions' => Craft::t('formie', 'Add any additional options for the date picker to use. For available options, refer to the [Flatpickr.js docs](https://flatpickr.js.org/options/).'),
                'name' => 'datePickerOptions',
                'validation' => 'min:0',
                'if' => 'displayType == "datePicker"',
                'columns' => [
                    [
                        'type' => 'text',
                        'name' => 'label',
                        'label' => Craft::t('formie', 'Option'),
                        'required' => true,
                    ],
                    [
                        'type' => 'value',
                        'name' => 'value',
                        'label' => Craft::t('formie', 'Value'),
                        'source' => 'label',
                    ],
                ],
            ]),
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
            SchemaHelper::subFieldLabelPosition([
                'if' => 'displayType != "calendar" && displayType != "datePicker"',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Date Format'),
                'instructions' => Craft::t('formie', 'Select what format to present dates as.'),
                'name' => 'dateFormat',
                'options' => $this->_getDateFormatOptions(),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Time Format'),
                'instructions' => Craft::t('formie', 'Select what format to present dates as.'),
                'name' => 'timeFormat',
                'options' => $this->_getTimeFormatOptions(),
            ]),
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
            SchemaHelper::inputAttributesField([
                'if' => 'displayType == "calendar" || displayType == "datePicker"',
            ]),
        ];
    }

    public function defineFormBuilderConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    public function getContentGqlType(): array|Type
    {
        if ($this->getCollectsRange()) {
            return $this->_getDateRangeGqlType();
        }

        return DateTimeType::getType();
    }

    public function getContentGqlMutationArgumentType(): Type|array
    {
        if ($this->getCollectsRange()) {
            return [
                'name' => $this->handle,
                'type' => $this->_getDateRangeGqlInputType(),
                'description' => $this->instructions->isEmpty() ? null : $this->instructions->toPlainText(),
            ];
        }

        return [
            'name' => $this->handle,
            'type' => DateTimeType::getType(),
            'description' => $this->instructions->isEmpty() ? null : $this->instructions->toPlainText(),
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
            'collectMode' => [
                'name' => 'collectMode',
                'type' => Type::string(),
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

    // Protected Methods
    // =========================================================================

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;
        $errors = $context->errors;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        // If using multiple fields, switch to fieldset. Basically anything other than a single-input datepicker.
        if ($this->displayType !== 'datePicker' || $this->getCollectsRange()) {
            if ($key === 'fieldLayout') {
                return SlotTag::make('fieldset')
                    ->core([
                        'data-formie-field-layout' => true,
                        'data-formie-date-field-layout' => true,
                        'data-formie-subfield-fieldset' => true,
                    ])
                    ->theme([
                        'class' => [
                            'formie-field-layout',
                            'formie-date-field-layout',
                            'formie-subfield-fieldset',
                        ],
                    ]);
            }

            if ($key === 'fieldLabel') {
                $labelPosition = $context->get('labelPosition');

                return SlotTag::make('legend')
                    ->core([
                        'data-formie-label' => true,
                        'data-formie-field-label' => true,
                        'data-formie-date-field-label' => true,
                        'data-formie-sr-only' => $labelPosition instanceof HiddenPosition ? true : false,
                    ])
                    ->theme([
                        'class' => [
                            'formie-label',
                            'formie-field-label',
                            'formie-date-field-label',
                            $labelPosition instanceof HiddenPosition ? 'formie-sr-only' : false,
                        ],
                    ]);
            }
        }

        if ($key === 'fieldInput' && $this->displayType === 'datePicker' && $this->getCollectsRange()) {
            return SlotTag::make('input')
                ->core(array_merge([
                    'type' => 'text',
                    'id' => $id,
                    'placeholder' => $this->getEffectivePlaceholder(),
                    'required' => $this->required ? true : null,
                    'autocomplete' => 'off',
                    'data-formie-input' => true,
                    'data-formie-date-input' => true,
                    'data-formie-date-datepicker-input' => true,
                    'data-formie-date-range-input' => true,
                    'data-formie-input-id' => $dataId,
                    'data-formie-input-type' => 'date',
                    'data-formie-input-error-state' => $errors ? true : false,
                    'aria-describedby' => $this->hasInstructions() ? "{$id}-instructions" : null,
                ], ValidationMessagesHelper::requiredClientAttributes($this)))
                ->theme([
                    'class' => [
                        'formie-input',
                        'formie-date-input',
                        $errors ? 'formie-input-error' : false,
                    ],
                ])
                ->instanceAttributes($this->getInputAttributes());
        }

        if ($key === 'fieldInput' && $this->displayType === 'datePicker' && !$this->getCollectsRange()) {
            return SlotTag::make('input')
                ->core(array_merge([
                    'type' => 'text',
                    'id' => $id,
                    'name' => $this->getHtmlName('datetime'),
                    'placeholder' => $this->getEffectivePlaceholder(),
                    'required' => $this->required ? true : null,
                    'autocomplete' => 'off',
                    'data-formie-input' => true,
                    'data-formie-date-input' => true,
                    'data-formie-input-id' => $dataId,
                    'data-formie-input-type' => 'date',
                    'data-formie-input-error-state' => $errors ? true : false,
                    'aria-describedby' => $this->hasInstructions() ? "{$id}-instructions" : null,
                ], ValidationMessagesHelper::requiredClientAttributes($this)))
                ->theme([
                    'class' => [
                        'formie-input',
                        'formie-date-input',
                        $errors ? 'formie-input-error' : false,
                    ],
                ])
                ->instanceAttributes($this->getInputAttributes());
        }

        return parent::defineFieldSlotTag($key, $context);
    }

    protected function supportedDefaults(): array
    {
        return ['displayType', 'collectMode', 'defaultOption', 'defaultValue'];
    }

    protected function getNestedLayoutBuilderLayouts(): array
    {
        return [
            'rows' => $this->getCalendarSubFields(),
            'layouts.dropdowns' => $this->getDropdownSubFields(),
            'layouts.inputs' => $this->getInputSubFields(),
            'layouts.calendar' => $this->getCalendarSubFields(),
            'layouts.calendarRange' => $this->getRangeCalendarSubFields(),
        ];
    }

    protected function defineSubFields(): array
    {
        if ($this->getCollectsRange()) {
            return $this->getRangeCalendarSubFields();
        }

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
            'defaultValue' => $this->_subFieldScaffoldDefaultValue('date'),
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
            'defaultValue' => $this->_subFieldScaffoldDefaultValue('time'),
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

        $date = DateFieldValue::toDateTime($this->getInitialValue()) ?: new DateTime();
        $year = (int)$date->format('Y');
        $minYear = $year - 100;
        $maxYear = $year + 100;

        if ($minDate = $this->getMinDate()) {
            $minYear = max($minYear, (int)$minDate->format('Y'));
        }

        if ($maxDate = $this->getMaxDate()) {
            $maxYear = min($maxYear, (int)$maxDate->format('Y'));
        }

        $fields[0]['fields'] = [
            [
                'type' => subfields\DateYearNumber::class,
                'label' => Craft::t('formie', 'Year'),
                'handle' => 'year',
                'enabled' => true,
                'labelPosition' => $this->subFieldLabelPosition,
                'limit' => true,
                'min' => $minYear,
                'max' => $maxYear,
            ],
            [
                'type' => subfields\DateMonthNumber::class,
                'label' => Craft::t('formie', 'Month'),
                'handle' => 'month',
                'enabled' => true,
                'labelPosition' => $this->subFieldLabelPosition,
                'limit' => true,
                'min' => 1,
                'max' => 12,
            ],
            [
                'type' => subfields\DateDayNumber::class,
                'label' => Craft::t('formie', 'Day'),
                'handle' => 'day',
                'enabled' => true,
                'labelPosition' => $this->subFieldLabelPosition,
                'limit' => true,
                'min' => 1,
                'max' => 31,
            ],
            [
                'type' => subfields\DateHourNumber::class,
                'label' => Craft::t('formie', 'Hour'),
                'handle' => 'hour',
                'enabled' => true,
                'labelPosition' => $this->subFieldLabelPosition,
                'limit' => true,
                'min' => 0,
                'max' => 23,
            ],
            [
                'type' => subfields\DateMinuteNumber::class,
                'label' => Craft::t('formie', 'Minute'),
                'handle' => 'minute',
                'enabled' => true,
                'labelPosition' => $this->subFieldLabelPosition,
                'limit' => true,
                'min' => 0,
                'max' => 59,
            ],
            [
                'type' => subfields\DateSecondNumber::class,
                'label' => Craft::t('formie', 'Second'),
                'handle' => 'second',
                'enabled' => true,
                'labelPosition' => $this->subFieldLabelPosition,
                'limit' => true,
                'min' => 0,
                'max' => 59,
            ],
            [
                'type' => subfields\DateAmPmDropdown::class,
                'label' => Craft::t('formie', 'AM/PM'),
                'handle' => 'ampm',
                'enabled' => false,
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

        $fields[0]['fields'] = [
            [
                'type' => subfields\DateYearDropdown::class,
                'label' => Craft::t('formie', 'Year'),
                'handle' => 'year',
                'enabled' => true,
                'labelPosition' => $this->subFieldLabelPosition,
                'options' => [],
            ],
            [
                'type' => subfields\DateMonthDropdown::class,
                'label' => Craft::t('formie', 'Month'),
                'handle' => 'month',
                'enabled' => true,
                'labelPosition' => $this->subFieldLabelPosition,
                'options' => $this->_getMonthOptions(),
            ],
            [
                'type' => subfields\DateDayDropdown::class,
                'label' => Craft::t('formie', 'Day'),
                'handle' => 'day',
                'enabled' => true,
                'labelPosition' => $this->subFieldLabelPosition,
                'options' => $this->_generateOptions(1, 31),
            ],
            [
                'type' => subfields\DateHourDropdown::class,
                'label' => Craft::t('formie', 'Hour'),
                'handle' => 'hour',
                'enabled' => true,
                'labelPosition' => $this->subFieldLabelPosition,
                'options' => $this->_generateOptions(0, 23),
            ],
            [
                'type' => subfields\DateMinuteDropdown::class,
                'label' => Craft::t('formie', 'Minute'),
                'handle' => 'minute',
                'enabled' => true,
                'labelPosition' => $this->subFieldLabelPosition,
                'options' => $this->_generateOptions(0, 59),
            ],
            [
                'type' => subfields\DateSecondDropdown::class,
                'label' => Craft::t('formie', 'Second'),
                'handle' => 'second',
                'enabled' => true,
                'labelPosition' => $this->subFieldLabelPosition,
                'options' => $this->_generateOptions(0, 59),
            ],
            [
                'type' => subfields\DateAmPmDropdown::class,
                'label' => Craft::t('formie', 'AM/PM'),
                'handle' => 'ampm',
                'enabled' => false,
                'labelPosition' => $this->subFieldLabelPosition,
                'options' => [
                    ['value' => 'AM', 'label' => Craft::t('formie', 'AM')],
                    ['value' => 'PM', 'label' => Craft::t('formie', 'PM')],
                ],
            ],
        ];

        return $fields;
    }

    protected function getRangeCalendarSubFields(): array
    {
        $baseFields = $this->_getDatePickerSubFields()[0]['fields'] ?? [];

        return [[
            'fields' => array_merge(
                $this->_buildRangeSideSubFields($baseFields, 'start', Craft::t('formie', 'Start')),
                $this->_buildRangeSideSubFields($baseFields, 'end', Craft::t('formie', 'End')),
            ),
        ]];
    }

    protected function _getDatePickerSubFields(): array
    {
        $fields = [];
        $inputAttributes = array_merge(($this->inputAttributes ?? []), [
            [
                'label' => 'autocomplete',
                'value' => 'off',
            ],
        ]);

        $fields[0]['fields'][] = [
            'type' => subfields\DateDate::class,
            'label' => Craft::t('formie', 'Date'),
            'handle' => 'date',
            'required' => $this->required,
            'placeholder' => $this->placeholder,
            'defaultValue' => $this->_subFieldScaffoldDefaultValue('date'),
            'labelPosition' => HiddenPosition::class,
            'inputAttributes' => $inputAttributes,
        ];

        $fields[0]['fields'][] = [
            'type' => subfields\DateTime::class,
            'label' => Craft::t('formie', 'Time'),
            'handle' => 'time',
            'required' => $this->required,
            'placeholder' => $this->placeholder,
            'defaultValue' => $this->_subFieldScaffoldDefaultValue('time'),
            'labelPosition' => HiddenPosition::class,
            'inputAttributes' => $inputAttributes,
        ];

        return $fields;
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
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
        if ($this->getCollectsRange()) {
            $rangeValue = $value instanceof DateRangeFieldValue
                ? $value
                : DateRangeFieldValue::fromMixed($value);

            if ($rangeValue->isEmpty()) {
                return '';
            }

            $this->_applyDisplaySettings($rangeValue);

            return $rangeValue->formatForDisplay();
        }

        if ($value instanceof DateFieldValue) {
            if ($value->isEmpty()) {
                return '';
            }

            $this->_applyDisplaySettings($value);

            return $value->formatPartsForDisplay($value->getParts());
        }

        $fieldValue = new DateFieldValue(DateFieldValue::parseParts($value));

        if ($fieldValue->isEmpty()) {
            return '';
        }

        $this->_applyDisplaySettings($fieldValue);

        return $fieldValue->formatPartsForDisplay($fieldValue->getParts());
    }

    protected function defineValueAsArray(mixed $value, ElementInterface $element = null): mixed
    {
        if ($this->getCollectsRange()) {
            $rangeValue = $value instanceof DateRangeFieldValue
                ? $value
                : DateRangeFieldValue::fromMixed($value);

            if ($rangeValue->isEmpty()) {
                return [];
            }

            return [
                'start' => $this->formatPartsForDisplay($rangeValue->getStartParts()),
                'end' => $this->formatPartsForDisplay($rangeValue->getEndParts()),
            ];
        }

        $stringValue = $this->getValueAsString($value, $element);

        return $stringValue !== '' ? [$stringValue] : [];
    }

    protected function defineValueForExport(mixed $value, ElementInterface $element = null): mixed
    {
        if ($this->getCollectsRange()) {
            $rangeValue = $value instanceof DateRangeFieldValue
                ? $value
                : DateRangeFieldValue::fromMixed($value);

            if ($rangeValue->isEmpty()) {
                return [];
            }

            return [
                $this->getExportLabel($element) . ': ' . Craft::t('formie', 'Start') => $this->formatPartsForDisplay($rangeValue->getStartParts()),
                $this->getExportLabel($element) . ': ' . Craft::t('formie', 'End') => $this->formatPartsForDisplay($rangeValue->getEndParts()),
            ];
        }

        return $this->getValueAsString($value, $element);
    }

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): string
    {
        return $this->getValueAsString($value, $element);
    }

    protected function defineValueForIntegration(mixed $value, IntegrationField $integrationField, IntegrationInterface $integration, ElementInterface $element = null, string $fieldKey = ''): mixed
    {
        $parts = DateFieldValue::parseParts($value);

        // If a string value is requested for a date, return the ISO 8601 date string
        if ($integrationField->getType() === IntegrationField::TYPE_STRING) {
            
        }

        if ($integrationField->getType() === IntegrationField::TYPE_DATE) {
            
        }

        if ($integrationField->getType() === IntegrationField::TYPE_DATETIME) {
            
        }

        if ($integrationField->getType() === IntegrationField::TYPE_DATECLASS) {
            
        }

        // Fetch the default handling
        return parent::defineValueForIntegration($value, $integrationField, $integration, $element);
    }

    protected function defineValueForEmailPreview(FakerFactory $faker): mixed
    {
        if ($this->getCollectsRange()) {
            $start = $faker->dateTimeBetween('-1 year', '-1 month');
            $end = $faker->dateTimeBetween($start, (clone $start)->modify('+30 days'));

            return new DateRangeFieldValue([
                'start' => DateRangeFieldValue::parseSideParts($start),
                'end' => DateRangeFieldValue::parseSideParts($end),
            ]);
        }

        return $faker->dateTime();
    }

    protected function defineValidationRules(): array
    {
        $validators = parent::defineValidationRules();

        if (!in_array($this->displayType, ['inputs', 'dropdowns'], true)) {
            return $validators;
        }

        $rule = ['type' => 'dateParts'];

        if ($minDate = $this->getMinDate()) {
            $rule['minDate'] = $minDate->format('Y-m-d\TH:i:s');
        }

        if ($maxDate = $this->getMaxDate()) {
            $rule['maxDate'] = $maxDate->format('Y-m-d\TH:i:s');
        }

        $validators[] = $rule;

        return $validators;
    }

    protected function defineClientInput(): array
    {
        $input = array_merge(parent::defineClientInput(), [
            'collectMode' => $this->collectMode,
            'dateEnabled' => $this->getIsDate(),
            'timeEnabled' => $this->getIsTime(),
        ]);

        if ($placeholder = $this->getEffectivePlaceholder()) {
            $input['placeholder'] = $placeholder;
        }

        return $input;
    }

    protected function defineClientModules(): array
    {
        $modules = parent::defineClientModules();

        if ($this->displayType === 'datePicker') {
            $locale = Craft::$app->getLocale()->id;

            if (preg_match('/^([a-z]{2})-/', $locale, $matches)) {
                $locale = $matches[1];
            }

            $supportedLocales = ['ar', 'at', 'az', 'be', 'bg', 'bn', 'cat', 'cs', 'cy', 'da', 'de', 'eo', 'es', 'et', 'fa', 'fi', 'fo', 'fr', 'gr', 'he', 'hi', 'hr', 'hu', 'id', 'is', 'it', 'ja', 'km', 'ko', 'kz', 'lt', 'lv', 'mk', 'mn', 'ms', 'my', 'nl', 'no', 'pa', 'pl', 'pt', 'ro', 'ru', 'si', 'sk', 'sl', 'sq', 'sr-cyr', 'sr', 'sv', 'th', 'tr', 'uk', 'vn', 'zh-tw', 'zh'];

            if (in_array(strtolower($locale), $supportedLocales, true)) {
                $locale = strtolower($locale);
            }

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

            $datePickerOptions = $this->datePickerOptions ?? [];

            foreach ($datePickerOptions as $key => $option) {
                $datePickerOptions[$key]['value'] = Json::decodeIfJson($option['value']);
            }

            $modules[] = new ClientModule([
                'id' => 'date-picker',
                'config' => [
                    'includeFlatpickrCss' => Formie::$plugin->getSettings()->includeFlatpickrCss,
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
                    'collectMode' => $this->collectMode,
                ],
            ]);
        }

        return $modules;
    }

    protected function defineValueClass(): ?string
    {
        return $this->getCollectsRange() ? DateRangeFieldValue::class : DateFieldValue::class;
    }

    protected function defineReferenceValues(): array
    {
        // Reference config is built from a blank field instance, so use `if` conditions
        // that the CP evaluates per form field — not runtime branching here.
        $rangeCondition = 'collectMode == "' . self::COLLECT_RANGE . '" && displayType == "datePicker"';
        $singleCalendarCondition = 'displayType == "calendar" || (displayType == "datePicker" && collectMode != "' . self::COLLECT_RANGE . '")';

        return [
            FieldReferenceValue::default([
                'handle' => '__toString',
                'label' => Craft::t('formie', 'Formatted Date'),
                'variableTypes' => [Variables::TYPE_DATE, Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'start',
                'label' => Craft::t('formie', 'Start Date/Time'),
                'if' => $rangeCondition,
                'variableTypes' => [Variables::TYPE_DATE],
            ]),
            FieldReferenceValue::property([
                'handle' => 'end',
                'label' => Craft::t('formie', 'End Date/Time'),
                'if' => $rangeCondition,
                'variableTypes' => [Variables::TYPE_DATE],
            ]),
            FieldReferenceValue::property([
                'handle' => 'startDate',
                'label' => Craft::t('formie', 'Start Date'),
                'if' => $rangeCondition,
                'variableTypes' => [Variables::TYPE_DATE],
            ]),
            FieldReferenceValue::property([
                'handle' => 'startTime',
                'label' => Craft::t('formie', 'Start Time'),
                'if' => $rangeCondition,
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'endDate',
                'label' => Craft::t('formie', 'End Date'),
                'if' => $rangeCondition,
                'variableTypes' => [Variables::TYPE_DATE],
            ]),
            FieldReferenceValue::property([
                'handle' => 'endTime',
                'label' => Craft::t('formie', 'End Time'),
                'if' => $rangeCondition,
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'date',
                'label' => Craft::t('formie', 'Date'),
                'if' => $singleCalendarCondition,
                'variableTypes' => [Variables::TYPE_DATE],
            ]),
            FieldReferenceValue::property([
                'handle' => 'time',
                'label' => Craft::t('formie', 'Time'),
                'if' => $singleCalendarCondition,
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'year',
                'label' => Craft::t('formie', 'Year'),
                'if' => 'displayType == "dropdowns" || displayType == "inputs"',
                'variableTypes' => [Variables::TYPE_NUMBER, Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'month',
                'label' => Craft::t('formie', 'Month'),
                'if' => 'displayType == "dropdowns" || displayType == "inputs"',
                'variableTypes' => [Variables::TYPE_NUMBER, Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'day',
                'label' => Craft::t('formie', 'Day'),
                'if' => 'displayType == "dropdowns" || displayType == "inputs"',
                'variableTypes' => [Variables::TYPE_NUMBER, Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'hour',
                'label' => Craft::t('formie', 'Hour'),
                'if' => 'displayType == "dropdowns" || displayType == "inputs"',
                'variableTypes' => [Variables::TYPE_NUMBER, Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'minute',
                'label' => Craft::t('formie', 'Minute'),
                'if' => 'displayType == "dropdowns" || displayType == "inputs"',
                'variableTypes' => [Variables::TYPE_NUMBER, Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'second',
                'label' => Craft::t('formie', 'Second'),
                'if' => 'displayType == "dropdowns" || displayType == "inputs"',
                'variableTypes' => [Variables::TYPE_NUMBER, Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'ampm',
                'label' => Craft::t('formie', 'AM/PM'),
                'if' => 'displayType == "dropdowns" || displayType == "inputs"',
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
        ];
    }


    // Private Methods
    // =========================================================================

    private function _resolveTodayDefaultDateTime(): DateTime
    {
        // Date-only fields should default to the start of today; date/time and
        // time-only fields should default to the current wall-clock moment.
        $dateTime = $this->getIsDate() ? new DateTime('today') : new DateTime();
        $resolved = DateTimeHelper::toDateTime($dateTime, false, false);

        return $resolved instanceof DateTime ? $resolved : new DateTime();
    }

    private function _applyDisplaySettings(DateFieldValue|DateRangeFieldValue $value): void
    {
        $includeDate = $this->getIsDate() || $this->getIsDateTime();
        $includeTime = $this->getIsTime() || $this->getIsDateTime();

        if (!$includeDate && !$includeTime) {
            $includeDate = true;
            $includeTime = true;
        }

        $value->applyDisplaySettings(
            $this->getDateFormat() ?: 'Y-m-d',
            $this->getTimeFormat() ?: 'H:i',
            $includeDate,
            $includeTime,
        );
    }

    private function _generateOptions(int $start, int $end, ?string $placeholder = null): array
    {
        $options = [['value' => '', 'label' => $placeholder, 'disabled' => true]];

        for ($i = $start; $i <= $end; $i++) {
            $options[] = ['label' => $i, 'value' => $i];
        }

        return $options;
    }

    private function _syncRowsFromActiveLayout(): void
    {
        $layoutKey = $this->_getActiveLayoutKey();

        if (!$layoutKey) {
            return;
        }

        $rows = is_array($this->layouts) ? ($this->layouts[$layoutKey] ?? null) : null;
        $rows = (is_array($rows) && $rows) ? $rows : $this->_getRowsForLayoutKey($layoutKey);

        if (is_array($rows)) {
            $this->setRows($rows);
        }
    }

    private function _getActiveLayoutKey(): ?string
    {
        return match ($this->displayType) {
            'dropdowns' => 'dropdowns',
            'inputs' => 'inputs',
            'datePicker' => $this->getCollectsRange() ? 'calendarRange' : 'calendar',
            'calendar' => 'calendar',
            default => null,
        };
    }

    private function _getRowsForLayoutKey(string $layoutKey): ?array
    {
        if ($this->getCollectsRange()) {
            return $layoutKey === 'calendarRange' ? $this->getRangeCalendarSubFields() : null;
        }

        return match ($layoutKey) {
            'dropdowns' => $this->getDropdownSubFields(),
            'inputs' => $this->getInputSubFields(),
            'calendar', 'calendarRange' => $this->getCalendarSubFields(),
            default => null,
        };
    }

    private static function _valueSqlForPart(array $instances, string $partKey): ?string
    {
        $db = Craft::$app->getDb();
        $qb = $db->getQueryBuilder();
        $sqlByInstance = [];

        foreach ($instances as $instance) {
            if (!$instance instanceof self || !$instance->uid) {
                continue;
            }

            $partSql = $qb->jsonExtract('formie_submissions.content', [$instance->uid, $partKey]);
            if ($partKey === 'ampm') {
                $columnType = 'CHAR(2)';
            } else {
                $columnType = $db->getIsPgsql() ? 'INTEGER' : 'SIGNED';
            }
            $sqlByInstance[] = "CAST($partSql AS $columnType)";
        }

        if (empty($sqlByInstance)) {
            return null;
        }

        if (count($sqlByInstance) === 1) {
            return $sqlByInstance[0];
        }

        return sprintf('COALESCE(%s)', implode(',', $sqlByInstance));
    }

    private static function _valueSqlForComparable(array $instances): ?string
    {
        $db = Craft::$app->getDb();
        $qb = $db->getQueryBuilder();
        $sqlByInstance = [];

        foreach ($instances as $instance) {
            if (!$instance instanceof self || !$instance->uid) {
                continue;
            }

            $year = self::_partSqlAsText($qb, $db, $instance->uid, 'year');
            $month = self::_partSqlAsText($qb, $db, $instance->uid, 'month');
            $day = self::_partSqlAsText($qb, $db, $instance->uid, 'day');
            $hour = self::_partSqlAsText($qb, $db, $instance->uid, 'hour');
            $minute = self::_partSqlAsText($qb, $db, $instance->uid, 'minute');
            $second = self::_partSqlAsText($qb, $db, $instance->uid, 'second');

            $sqlByInstance[] = "CASE WHEN $year IS NOT NULL AND $month IS NOT NULL AND $day IS NOT NULL THEN CONCAT(LPAD($year, 4, '0'), LPAD($month, 2, '0'), LPAD($day, 2, '0'), LPAD(COALESCE($hour, '0'), 2, '0'), LPAD(COALESCE($minute, '0'), 2, '0'), LPAD(COALESCE($second, '0'), 2, '0')) ELSE NULL END";
        }

        if (empty($sqlByInstance)) {
            return null;
        }

        if (count($sqlByInstance) === 1) {
            return $sqlByInstance[0];
        }

        return sprintf('COALESCE(%s)', implode(',', $sqlByInstance));
    }

    private static function _partSqlAsText(object $qb, object $db, string $fieldUid, string $partKey): string
    {
        $partSql = $qb->jsonExtract('formie_submissions.content', [$fieldUid, $partKey]);
        $textSql = "TRIM(BOTH '\"' FROM CAST($partSql AS TEXT))";

        if ($db->getIsMysql()) {
            $textSql = "TRIM(BOTH '\"' FROM CAST($partSql AS CHAR(16)))";
        }

        return "NULLIF($textSql, '')";
    }

    private static function _normalizeComparableQueryValue(mixed $value): mixed
    {
        if (is_array($value)) {
            if (empty($value)) {
                return null;
            }

            $operator = strtolower((string)array_shift($value));

            if ($operator !== 'and' && $operator !== 'or' && $operator !== 'not') {
                return null;
            }

            $normalized = [$operator];

            foreach ($value as $item) {
                $leaf = self::_normalizeComparableQueryValue($item);

                if ($leaf === null) {
                    return null;
                }

                $normalized[] = $leaf;
            }

            return $normalized;
        }

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if ($value === ':empty:' || $value === ':notempty:') {
            return $value;
        }

        if (!preg_match('/^(?:(>=|<=|<>|!=|>|<|=)\s*)?(.*)$/', $value, $matches)) {
            return null;
        }

        $operator = $matches[1] ?? '';
        $operand = trim($matches[2] ?? '');

        if ($operand === '') {
            return null;
        }

        $comparable = self::_comparableKeyFromOperand($operand);

        if ($comparable === null) {
            return null;
        }

        return trim(($operator ? "$operator " : '') . $comparable);
    }

    private static function _comparableKeyFromOperand(string $operand): ?string
    {
        $parsed = date_parse($operand);

        if (($parsed['error_count'] ?? 0) > 0) {
            return null;
        }

        if (!isset($parsed['year'], $parsed['month'], $parsed['day']) || !$parsed['year'] || !$parsed['month'] || !$parsed['day']) {
            return null;
        }

        $hour = ($parsed['hour'] ?? null) !== null ? (int)$parsed['hour'] : 0;
        $minute = ($parsed['minute'] ?? null) !== null ? (int)$parsed['minute'] : 0;
        $second = ($parsed['second'] ?? null) !== null ? (int)$parsed['second'] : 0;

        return sprintf(
            '%04d%02d%02d%02d%02d%02d',
            (int)$parsed['year'],
            (int)$parsed['month'],
            (int)$parsed['day'],
            $hour,
            $minute,
            $second
        );
    }

    /**
     * Normalize incoming request-style date values into canonical part maps
     * without leaking presentation format concerns into DateFieldValue.
     */
    private function _normalizeInputValue(mixed $value): mixed
    {
        if ($value instanceof OptionValue || $value instanceof SingleOptionFieldValue) {
            return $value->value;
        }

        if (!is_array($value)) {
            return $value;
        }

        if ($this->getCollectsRange()) {
            return $this->_normalizeRangeInputValue($value);
        }

        if ($this->displayType === 'inputs' || $this->displayType === 'dropdowns') {
            return $this->_normalizeInputPartsArray($value);
        }

        $datePart = trim((string)($value['date'] ?? ''));
        $timePart = trim((string)($value['time'] ?? ''));
        $datetimePart = trim((string)($value['datetime'] ?? ''));
        $parts = [];

        if ($datetimePart !== '') {
            $parts = $this->_parseDateTimeByConfiguredFormats($datetimePart);

            if (!empty($parts)) {
                return DateFieldValue::normalizeParts($parts);
            }
        }

        if ($datePart !== '') {
            $parts = array_merge($parts, $this->_parseDateByConfiguredFormat($datePart));
        }

        if ($timePart !== '') {
            $parts = array_merge($parts, $this->_parseTimeByConfiguredFormat($timePart));
        }

        if (!empty($parts)) {
            return DateFieldValue::normalizeParts($parts);
        }

        if ($datetimePart !== '') {
            return $datetimePart;
        }

        return $value;
    }

    private function _normalizeInputPartsArray(array $value): array
    {
        $parts = [];
        $keys = ['year', 'month', 'day', 'hour', 'minute', 'second', 'ampm'];

        foreach ($keys as $key) {
            $partValue = $value[$key] ?? null;

            if ($partValue instanceof OptionValue || $partValue instanceof SingleOptionFieldValue) {
                $partValue = $partValue->value;
            }

            $parts[$key] = $partValue;
        }

        return DateFieldValue::normalizeParts($parts);
    }

    private function _parseDateByConfiguredFormat(string $value): array
    {
        $date = DateTime::createFromFormat($this->getDateFormat(), $value, new DateTimeZone('UTC'));

        if ($date instanceof DateTime) {
            return [
                'year' => $date->format('Y'),
                'month' => $date->format('n'),
                'day' => $date->format('j'),
            ];
        }

        return DateFieldValue::parseParts(['date' => $value]);
    }

    private function _parseTimeByConfiguredFormat(string $value): array
    {
        $time = DateTime::createFromFormat($this->getTimeFormat(), $value, new DateTimeZone('UTC'));

        if ($time instanceof DateTime) {
            return [
                'hour' => $time->format('G'),
                'minute' => $time->format('i'),
                'second' => $time->format('s'),
                'ampm' => strtoupper($time->format('A')),
            ];
        }

        return DateFieldValue::parseParts(['time' => $value]);
    }

    private function _parseDateTimeByConfiguredFormats(string $value): array
    {
        $formats = array_filter([
            trim($this->getDateFormat() . ' ' . $this->getTimeFormat()),
            $this->getDateFormat(),
            $this->getTimeFormat(),
        ]);

        foreach (array_unique($formats) as $format) {
            $dateTime = DateTime::createFromFormat($format, $value, new DateTimeZone('UTC'));

            if (!$dateTime instanceof DateTime) {
                continue;
            }

            $parts = [];

            if (str_contains($format, 'Y') || str_contains($format, 'y')) {
                $parts['year'] = $dateTime->format('Y');
            }

            if (str_contains($format, 'm') || str_contains($format, 'n')) {
                $parts['month'] = $dateTime->format('n');
            }

            if (str_contains($format, 'd') || str_contains($format, 'j')) {
                $parts['day'] = $dateTime->format('j');
            }

            if (str_contains($format, 'H') || str_contains($format, 'G') || str_contains($format, 'h') || str_contains($format, 'g')) {
                $parts['hour'] = $dateTime->format('G');
            }

            if (str_contains($format, 'i')) {
                $parts['minute'] = $dateTime->format('i');
            }

            if (str_contains($format, 's')) {
                $parts['second'] = $dateTime->format('s');
            }

            if (str_contains($format, 'A') || str_contains($format, 'a')) {
                $parts['ampm'] = strtoupper($dateTime->format('A'));
            }

            if (!empty($parts)) {
                return $parts;
            }
        }

        return [];
    }

    private function _getMonthOptions(?string $placeholder = null): array
    {
        $options = [['value' => '', 'label' => $placeholder, 'disabled' => true]];

        foreach (Craft::$app->getLocale()->getMonthNames() as $index => $monthName) {
            $options[] = ['value' => $index + 1, 'label' => $monthName];
        }

        return $options;
    }

    private function _getDateFormatOptions(): array
    {
        $options = [
            ['label' => Craft::t('formie', 'YYYY-MM-DD'), 'value' => 'Y-m-d'],
            ['label' => Craft::t('formie', 'MM-DD-YYYY'), 'value' => 'm-d-Y'],
            ['label' => Craft::t('formie', 'DD-MM-YYYY'), 'value' => 'd-m-Y'],
            ['label' => Craft::t('formie', 'YYYY/MM/DD'), 'value' => 'Y/m/d'],
            ['label' => Craft::t('formie', 'MM/DD/YYYY'), 'value' => 'm/d/Y'],
            ['label' => Craft::t('formie', 'DD/MM/YYYY'), 'value' => 'd/m/Y'],
            ['label' => Craft::t('formie', 'YYYY.MM.DD'), 'value' => 'Y.m.d'],
            ['label' => Craft::t('formie', 'MM.DD.YYYY'), 'value' => 'm.d.Y'],
            ['label' => Craft::t('formie', 'DD.MM.YYYY'), 'value' => 'd.m.Y'],
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
            ['label' => Craft::t('formie', '23:59:59 (HH:MM:SS)'), 'value' => 'H:i:s'],
            ['label' => Craft::t('formie', '03:59:59 PM (H:MM:SS AM/PM)'), 'value' => 'h:i:s A'],
            ['label' => Craft::t('formie', '23:59 (HH:MM)'), 'value' => 'H:i'],
            ['label' => Craft::t('formie', '03:59 PM (H:MM AM/PM)'), 'value' => 'h:i A'],
            ['label' => Craft::t('formie', '59:59 (MM:SS)'), 'value' => 'i:s'],
        ];

        $event = new RegisterDateTimeFormatOptionsEvent([
            'field' => $this,
            'options' => $options,
        ]);
        $this->trigger(self::EVENT_REGISTER_TIME_FORMAT_OPTIONS, $event);

        return $event->options;
    }

    private function _getFormatPlaceholderLabel(): ?string
    {
        $parts = [];

        if ($this->getIsDate() || $this->getIsDateTime()) {
            $label = $this->_getFormatOptionLabel($this->getDateFormat(), $this->_getDateFormatOptions());

            if ($label) {
                $parts[] = $label;
            }
        }

        if ($this->getIsTime() || $this->getIsDateTime()) {
            $label = $this->_getFormatOptionLabel($this->getTimeFormat(), $this->_getTimeFormatOptions());

            if ($label) {
                $parts[] = $label;
            }
        }

        return $parts ? implode(' ', $parts) : null;
    }

    private function _getFormatOptionLabel(?string $format, array $options): ?string
    {
        foreach ($options as $option) {
            if (($option['value'] ?? null) === $format) {
                return $option['label'] ?? null;
            }
        }

        return null;
    }

    private function _buildRangeSideSubFields(array $fieldDefinitions, string $prefix, string $sideLabel): array
    {
        $fields = [];

        foreach ($fieldDefinitions as $fieldDefinition) {
            $handle = (string)($fieldDefinition['handle'] ?? '');

            if ($handle === '') {
                continue;
            }

            $fieldDefinition['handle'] = $prefix . ucfirst($handle);
            $fieldDefinition['label'] = Craft::t('formie', '{side} {label}', [
                'side' => $sideLabel,
                'label' => $fieldDefinition['label'] ?? ucfirst($handle),
            ]);
            $fieldDefinition['required'] = $this->required;
            $fieldDefinition['labelPosition'] = $this->subFieldLabelPosition ?? ($fieldDefinition['labelPosition'] ?? null);

            $fields[] = $fieldDefinition;
        }

        return $fields;
    }

    private function _normalizeRangeInputValue(array $value): array
    {
        if (isset($value['start']) || isset($value['end'])) {
            return [
                'start' => $this->_normalizeRangeSideInputValue(is_array($value['start'] ?? null) ? $value['start'] : []),
                'end' => $this->_normalizeRangeSideInputValue(is_array($value['end'] ?? null) ? $value['end'] : []),
            ];
        }

        return [
            'start' => $this->_normalizeRangeSideInputValue($value, 'start'),
            'end' => $this->_normalizeRangeSideInputValue($value, 'end'),
        ];
    }

    private function _normalizeRangeSideInputValue(array $value, ?string $prefix = null): array
    {
        if ($prefix !== null) {
            if ($this->displayType === 'inputs' || $this->displayType === 'dropdowns') {
                $parts = [];

                foreach (DateRangeFieldValue::partKeys() as $partKey) {
                    $prefixedKey = $prefix . ucfirst($partKey);

                    if (array_key_exists($prefixedKey, $value)) {
                        $partValue = $value[$prefixedKey];

                        if ($partValue instanceof OptionValue || $partValue instanceof SingleOptionFieldValue) {
                            $partValue = $partValue->value;
                        }

                        $parts[$partKey] = $partValue;
                    }
                }

                return DateFieldValue::normalizeParts($parts);
            }

            $sideValue = [
                'date' => $value[$prefix . 'Date'] ?? '',
                'time' => $value[$prefix . 'Time'] ?? '',
                'datetime' => $value[$prefix . 'Datetime'] ?? ($value[$prefix . 'DateTime'] ?? ''),
            ];

            return DateFieldValue::parseParts($this->_normalizeSingleCalendarInputValue($sideValue));
        }

        if ($this->displayType === 'inputs' || $this->displayType === 'dropdowns') {
            return $this->_normalizeInputPartsArray($value);
        }

        return DateFieldValue::parseParts($this->_normalizeSingleCalendarInputValue($value));
    }

    private function _normalizeSingleCalendarInputValue(array $value): mixed
    {
        $datePart = trim((string)($value['date'] ?? ''));
        $timePart = trim((string)($value['time'] ?? ''));
        $datetimePart = trim((string)($value['datetime'] ?? ''));
        $parts = [];

        if ($datetimePart !== '') {
            $parts = $this->_parseDateTimeByConfiguredFormats($datetimePart);

            if (!empty($parts)) {
                return DateFieldValue::normalizeParts($parts);
            }
        }

        if ($datePart !== '') {
            $parts = array_merge($parts, $this->_parseDateByConfiguredFormat($datePart));
        }

        if ($timePart !== '') {
            $parts = array_merge($parts, $this->_parseTimeByConfiguredFormat($timePart));
        }

        if (!empty($parts)) {
            return DateFieldValue::normalizeParts($parts);
        }

        if ($datetimePart !== '') {
            return $datetimePart;
        }

        return $value;
    }

    private function _getDateRangeGqlTypeName(string $suffix): string
    {
        $formHandle = $this->getForm()?->handle ?? 'form';
        return "{$formHandle}_{$this->handle}_FormieDateRange{$suffix}";
    }

    private function _getDateRangeGqlType(): Type
    {
        $typeName = $this->_getDateRangeGqlTypeName('');

        if ($type = GqlEntityRegistry::getEntity($typeName)) {
            return $type;
        }

        return GqlEntityRegistry::createEntity($typeName, new ObjectType([
            'name' => $typeName,
            'fields' => [
                'start' => [
                    'name' => 'start',
                    'type' => DateTimeType::getType(),
                    'resolve' => fn(array $source) => DateFieldValue::toDateTime($source['start'] ?? []),
                ],
                'end' => [
                    'name' => 'end',
                    'type' => DateTimeType::getType(),
                    'resolve' => fn(array $source) => DateFieldValue::toDateTime($source['end'] ?? []),
                ],
            ],
        ]));
    }

    private function _getDateRangeGqlInputType(): Type
    {
        $typeName = $this->_getDateRangeGqlTypeName('Input');

        if ($type = GqlEntityRegistry::getEntity($typeName)) {
            return $type;
        }

        return GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => fn() => [
                'start' => Type::nonNull(DateTimeType::getType()),
                'end' => Type::nonNull(DateTimeType::getType()),
            ],
        ]));
    }

    private function _collectEnabledDatePartsFromElement(ElementInterface $element): array
    {
        $parts = [];

        foreach (DateFieldValue::partKeys() as $partKey) {
            $field = $this->getFieldByHandle($partKey);

            if (!$field || !($field->enabled ?? true) || $field->getIsDisabled()) {
                continue;
            }

            $value = $element->getFieldValue($field->valueKey());

            if ($value instanceof OptionValue || $value instanceof SingleOptionFieldValue) {
                $value = $value->value;
            }

            $parts[$partKey] = $value;
        }

        return DateFieldValue::normalizeParts($parts);
    }

    private function _hasAnyDatePartValue(array $parts): bool
    {
        foreach ($parts as $value) {
            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    private function _hasAllEnabledDateParts(array $parts): bool
    {
        foreach (['year', 'month', 'day'] as $partKey) {
            $field = $this->getFieldByHandle($partKey);

            if (!$field || !($field->enabled ?? true) || $field->getIsDisabled()) {
                continue;
            }

            if (!isset($parts[$partKey]) || $parts[$partKey] === '') {
                return false;
            }
        }

        return true;
    }

    private static function _configCollectsRange(array $config): bool
    {
        return ($config['collectMode'] ?? self::COLLECT_SINGLE) === self::COLLECT_RANGE
            && ($config['displayType'] ?? '') === 'datePicker';
    }

    /**
     * @param array<int, mixed> $rows
     */
    private static function _clearSubFieldDefaultValues(array &$rows): void
    {
        foreach ($rows as &$row) {
            if (!is_array($row)) {
                continue;
            }

            foreach ($row['fields'] ?? [] as &$field) {
                if (!is_array($field)) {
                    continue;
                }

                if (array_key_exists('defaultValue', $field)) {
                    $field['defaultValue'] = null;
                }

                if (isset($field['settings']) && is_array($field['settings']) && array_key_exists('defaultValue', $field['settings'])) {
                    $field['settings']['defaultValue'] = null;
                }
            }
            unset($field);
        }
        unset($row);
    }

    /**
     * @param array<int, mixed> $rows
     */
    private function _sanitizeSubFieldRowsForBuilder(array &$rows): void
    {
        foreach ($rows as &$row) {
            if (!is_array($row)) {
                continue;
            }

            foreach ($row['fields'] ?? [] as &$field) {
                if (!is_array($field)) {
                    continue;
                }

                $handle = $field['settings']['handle'] ?? $field['handle'] ?? null;
                $handle = is_string($handle) && $handle !== '' ? $handle : null;

                if (array_key_exists('defaultValue', $field)) {
                    $field['defaultValue'] = $this->_normalizeBuilderSubFieldDefaultValue($field['defaultValue'], $handle);
                }

                if (isset($field['settings']) && is_array($field['settings']) && array_key_exists('defaultValue', $field['settings'])) {
                    $field['settings']['defaultValue'] = $this->_normalizeBuilderSubFieldDefaultValue(
                        $field['settings']['defaultValue'],
                        $handle,
                    );
                }
            }
            unset($field);
        }
        unset($row);
    }

    private function _normalizeBuilderSubFieldDefaultValue(mixed $defaultValue, ?string $handle): mixed
    {
        if ($this->defaultOption === 'today') {
            return null;
        }

        if ($defaultValue === null || $defaultValue === '') {
            return null;
        }

        if ($handle !== null) {
            $projected = $this->getSubFieldPartValue($defaultValue, $handle);

            if ($projected === null || $projected === '') {
                return null;
            }

            if (is_string($projected) || is_numeric($projected)) {
                return (string)$projected;
            }

            return null;
        }

        if (is_string($defaultValue) || is_numeric($defaultValue)) {
            return (string)$defaultValue;
        }

        return null;
    }

    /**
     * Subfield row templates should not persist dynamic "today" timestamps in the builder.
     */
    private function _subFieldScaffoldDefaultValue(?string $handle = null): mixed
    {
        if ($this->defaultOption === 'today') {
            return null;
        }

        return $this->_normalizeBuilderSubFieldDefaultValue($this->getInitialValue(), $handle);
    }

    private static function _gqlDateRangeTypeNameFromConfig(array $config, string $suffix): string
    {
        $formHandle = $config['formHandle'] ?? 'form';
        $fieldHandle = $config['handle'] ?? 'field';

        return "{$formHandle}_{$fieldHandle}_FormieDateRange{$suffix}";
    }

    private static function _gqlDateRangeTypeFromConfig(array $config): Type
    {
        $typeName = self::_gqlDateRangeTypeNameFromConfig($config, '');

        if ($type = GqlEntityRegistry::getEntity($typeName)) {
            return $type;
        }

        return GqlEntityRegistry::createEntity($typeName, new ObjectType([
            'name' => $typeName,
            'fields' => [
                'start' => [
                    'name' => 'start',
                    'type' => DateTimeType::getType(),
                    'resolve' => fn(array $source) => DateFieldValue::toDateTime($source['start'] ?? []),
                ],
                'end' => [
                    'name' => 'end',
                    'type' => DateTimeType::getType(),
                    'resolve' => fn(array $source) => DateFieldValue::toDateTime($source['end'] ?? []),
                ],
            ],
        ]));
    }

    private static function _gqlDateRangeInputTypeFromConfig(array $config): Type
    {
        $typeName = self::_gqlDateRangeTypeNameFromConfig($config, 'Input');

        if ($type = GqlEntityRegistry::getEntity($typeName)) {
            return $type;
        }

        return GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => fn() => [
                'start' => Type::nonNull(DateTimeType::getType()),
                'end' => Type::nonNull(DateTimeType::getType()),
            ],
        ]));
    }
}
