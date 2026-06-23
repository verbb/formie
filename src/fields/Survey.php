<?php
namespace verbb\formie\fields;

use verbb\formie\base\DisplayTypeFieldInterface;
use verbb\formie\base\Field as FormieField;
use verbb\formie\base\OptionsField;
use verbb\formie\base\QuestionnaireFieldInterface;
use verbb\formie\base\SortableFieldInterface;
use verbb\formie\fields\traits\DisplayTypeFieldTrait;
use verbb\formie\fields\traits\QuestionFieldTrait;
use verbb\formie\fields\values\LikertMultipleRowsFieldValue;
use verbb\formie\fields\values\MultiOptionFieldValue;
use verbb\formie\fields\values\SingleOptionFieldValue;
use verbb\formie\helpers\OptionsMode;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\SurveyPresentationDefaults;
use verbb\formie\helpers\ValidationMessagesHelper;
use verbb\formie\models\ClientModule;
use verbb\formie\models\RichText;
use verbb\formie\models\SlotTag;

use verbb\formie\elements\Form;
use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Json;
use craft\helpers\StringHelper;

class Survey extends OptionsField implements SortableFieldInterface, QuestionnaireFieldInterface, DisplayTypeFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Survey');
    }

    public static function translatableProperties(): array
    {
        $properties = parent::translatableProperties();
        $properties[] = 'question';
        $properties[] = 'answerExplanation';

        return $properties;
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/survey/icon.svg';
    }

    public static function phpType(): string
    {
        return sprintf('\\%s', SingleOptionFieldValue::class);
    }

    public static function defineFieldType(): array
    {
        return array_merge(parent::defineFieldType(), static::defineQuestionFieldTypeConfig());
    }


    // Constants
    // =========================================================================

    public const DISPLAY_LIKERT = 'likert';
    public const DISPLAY_RANK = 'rank';
    public const DISPLAY_RATING = 'rating';
    public const DISPLAY_DROPDOWN = 'dropdown';
    public const DISPLAY_RADIO = 'radio';
    public const DISPLAY_CHECKBOXES = 'checkboxes';
    public const DISPLAY_SINGLE_LINE_TEXT = 'singleLineText';
    public const DISPLAY_MULTI_LINE_TEXT = 'multiLineText';


    // Traits
    // =========================================================================

    use QuestionFieldTrait {
        _syncOptionValuesFromLabels as protected _syncQuestionOptionValuesFromLabels;
    }
    use DisplayTypeFieldTrait;


    // Properties
    // =========================================================================

    public RichText $question;
    public string $displayType = self::DISPLAY_RADIO;
    public ?string $layout = 'vertical';
    public ?string $starColor = null;
    public bool $scoringEnabled = false;
    public bool $multipleRowsEnabled = false;
    public array $likertRows = [];


    // Public Methods
    // =========================================================================

    public function settingsAttributes(): array
    {
        return array_merge(parent::settingsAttributes(), $this->defineDisplayTypeSettingsAttributes(), [
            'displayType',
            'starColor',
            'scoringEnabled',
            'multipleRowsEnabled',
            'likertRows',
        ]);
    }

    public function __construct(array $config = [])
    {
        $this->_initQuestionFieldConfig($config);
        $this->initDisplayTypeFieldConfig($config);

        parent::__construct($config);

        $this->_syncMultiFromDisplayType();

        if ($this->options === []) {
            $this->_applyPresentationDefaultOptions($this->displayType);
        }

        $this->_syncScoringEnabledFromOptionsMode();
        $this->_syncLikertMultipleRowsSettings();
    }

    public function setAttributes($values, $safeOnly = true): void
    {
        $previousDisplayType = $this->displayType;

        if (is_array($values) && array_key_exists('displayType', $values)) {
            $this->_syncMultiFromDisplayType($values['displayType'] ?? $this->displayType);
        }

        parent::setAttributes($values, $safeOnly);

        $this->_syncMultiFromDisplayType();

        if ($previousDisplayType !== $this->displayType) {
            $this->_applyPresentationDefaultOptions($previousDisplayType);
        }

        $this->_syncScoringEnabledFromOptionsMode();
        $this->_syncLikertMultipleRowsSettings();
    }

    public function getEffectiveLikertRows(): array
    {
        if (!$this->multipleRowsEnabled || !is_array($this->likertRows)) {
            return [];
        }

        $rows = [];
        $usedValues = [];

        foreach ($this->likertRows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $label = trim((string)($row['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $value = $this->_resolveLikertKey(
                (string)($row['value'] ?? ''),
                $usedValues,
                'row',
            );

            $usedValues[] = $value;
            $rows[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        return $rows;
    }

    public function usesLikertMultipleRows(): bool
    {
        return $this->displayType === self::DISPLAY_LIKERT
            && count($this->getEffectiveLikertRows()) >= 2;
    }

    public function getDefaultableSettingsSchema(): array
    {
        return [
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Presentation'),
                'instructions' => Craft::t('formie', 'The default presentation used when a new Survey field is added.'),
                'name' => 'displayType',
                'options' => [
                    ['label' => Craft::t('formie', 'Likert'), 'value' => self::DISPLAY_LIKERT],
                    ['label' => Craft::t('formie', 'Rank'), 'value' => self::DISPLAY_RANK],
                    ['label' => Craft::t('formie', 'Rating'), 'value' => self::DISPLAY_RATING],
                    ['label' => Craft::t('formie', 'Dropdown'), 'value' => self::DISPLAY_DROPDOWN],
                    ['label' => Craft::t('formie', 'Radio Buttons'), 'value' => self::DISPLAY_RADIO],
                    ['label' => Craft::t('formie', 'Checkboxes'), 'value' => self::DISPLAY_CHECKBOXES],
                    ['label' => Craft::t('formie', 'Single-line Text'), 'value' => self::DISPLAY_SINGLE_LINE_TEXT],
                    ['label' => Craft::t('formie', 'Multi-line Text'), 'value' => self::DISPLAY_MULTI_LINE_TEXT],
                ],
            ]),
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Likert Default Columns'),
                'instructions' => Craft::t('formie', 'Default likert scale columns applied to new Survey fields using the Likert presentation. Leave empty to use the built-in likert scale.'),
                'name' => 'likertDefaultOptions',
                'columns' => [
                    [
                        'type' => 'text',
                        'name' => 'label',
                        'label' => Craft::t('formie', 'Label'),
                        'required' => true,
                    ],
                ],
            ]),
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Rating Default Labels'),
                'instructions' => Craft::t('formie', 'Default rating labels applied to new Survey fields using the Rating presentation. Leave empty to use the built-in star rating scale.'),
                'name' => 'ratingDefaultOptions',
                'columns' => [
                    [
                        'type' => 'text',
                        'name' => 'label',
                        'label' => Craft::t('formie', 'Label'),
                        'required' => true,
                    ],
                ],
            ]),
        ];
    }

    protected function _syncMultiFromDisplayType(?string $displayType = null): void
    {
        $displayType ??= $this->displayType;

        $this->multi = in_array($displayType, [self::DISPLAY_CHECKBOXES, self::DISPLAY_RANK], true);
    }

    public function usesOptions(): bool
    {
        return in_array($this->displayType, [
            self::DISPLAY_LIKERT,
            self::DISPLAY_RANK,
            self::DISPLAY_RATING,
            self::DISPLAY_DROPDOWN,
            self::DISPLAY_RADIO,
            self::DISPLAY_CHECKBOXES,
        ], true);
    }

    public function usesStrictOptionValidation(): bool
    {
        if (!$this->usesOptions()) {
            return false;
        }

        return parent::usesStrictOptionValidation();
    }

    public function supportsQuestionnaireResults(): bool
    {
        return $this->usesOptions();
    }

    public static function defineQuestionnaireResultsWhen(): ?array
    {
        return [
            'property' => 'displayType',
            'values' => [
                self::DISPLAY_LIKERT,
                self::DISPLAY_RANK,
                self::DISPLAY_RATING,
                self::DISPLAY_DROPDOWN,
                self::DISPLAY_RADIO,
                self::DISPLAY_CHECKBOXES,
            ],
        ];
    }

    public function themeConfigKey(): string
    {
        return match ($this->displayType) {
            self::DISPLAY_CHECKBOXES => 'checkboxes',
            self::DISPLAY_DROPDOWN => 'dropdown',
            self::DISPLAY_SINGLE_LINE_TEXT => 'singleLineText',
            self::DISPLAY_MULTI_LINE_TEXT => 'multiLineText',
            self::DISPLAY_LIKERT => 'surveyLikert',
            self::DISPLAY_RANK => 'surveyRank',
            self::DISPLAY_RATING => 'surveyRating',
            default => 'radioButtons',
        };
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewChoiceList('checkbox', [
                'if' => 'field.displayType == "checkboxes"',
            ]),
            SchemaHelper::previewSelect([
                'if' => 'field.displayType == "dropdown"',
            ]),
            SchemaHelper::previewInput([
                'if' => 'field.displayType == "singleLineText"',
            ]),
            SchemaHelper::previewTextarea([
                'if' => 'field.displayType == "multiLineText"',
            ]),
            SchemaHelper::previewNode('PreviewLikert', [
                'options' => SchemaHelper::previewBind('field.options', []),
                'likertRows' => SchemaHelper::previewBind('field.likertRows', []),
                'multipleRowsEnabled' => SchemaHelper::previewBind('field.multipleRowsEnabled', false),
                'value' => SchemaHelper::previewBind('field.defaultValue', null),
                'useOptionDefaults' => true,
                'if' => 'field.displayType == "likert"',
            ]),
            SchemaHelper::previewNode('PreviewRank', [
                'options' => SchemaHelper::previewBind('field.options', []),
                'visibleLimit' => 5,
                'useOptionDefaults' => true,
                'if' => 'field.displayType == "rank"',
            ]),
            SchemaHelper::previewNode('PreviewRating', [
                'options' => SchemaHelper::previewBind('field.options', []),
                'value' => SchemaHelper::previewBind('field.defaultValue', null),
                'useOptionDefaults' => true,
                'starColor' => SchemaHelper::previewBind('field.starColor', null),
                'if' => 'field.displayType == "rating"',
            ]),
            SchemaHelper::previewChoiceList('radio', [
                'if' => 'field.displayType == "radio"',
            ]),
        ];
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        $optionsIf = 'displayType == "likert" || displayType == "rank" || displayType == "rating" || displayType == "dropdown" || displayType == "radio" || displayType == "checkboxes"';

        return [
            ...$this->defineQuestionGeneralSchema(),
            ...$this->defineLockedFieldTypeSchema('displayType', [
                ['label' => Craft::t('formie', 'Likert'), 'value' => self::DISPLAY_LIKERT],
                ['label' => Craft::t('formie', 'Rank'), 'value' => self::DISPLAY_RANK],
                ['label' => Craft::t('formie', 'Rating'), 'value' => self::DISPLAY_RATING],
                ['label' => Craft::t('formie', 'Dropdown'), 'value' => self::DISPLAY_DROPDOWN],
                ['label' => Craft::t('formie', 'Radio Buttons'), 'value' => self::DISPLAY_RADIO],
                ['label' => Craft::t('formie', 'Checkboxes'), 'value' => self::DISPLAY_CHECKBOXES],
                ['label' => Craft::t('formie', 'Single-line Text'), 'value' => self::DISPLAY_SINGLE_LINE_TEXT],
                ['label' => Craft::t('formie', 'Multi-line Text'), 'value' => self::DISPLAY_MULTI_LINE_TEXT],
            ], 'surveyDisplayType'),
            ...array_map(static function(array $field) use ($optionsIf) {
                if (($field['name'] ?? null) === 'optionsMode' || ($field['$field'] ?? null) === 'optionDynamicSettings') {
                    $field['if'] = isset($field['if']) ? "({$field['if']}) && ({$optionsIf})" : $optionsIf;
                }

                return $field;
            }, $this->defineOptionDynamicGeneralSchema()),
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Static Options'),
                'instructions' => Craft::t('formie', 'Add, remove, or reorder options manually.'),
                'name' => 'options',
                'if' => "({$optionsIf}) && optionsMode == \"static\" && displayType != \"likert\"",
                'syncQuestionOptionValues' => true,
                'enableOptionRowMenu' => true,
                'enableBulkOptions' => true,
                'predefinedOptions' => $this->getPredefinedOptions(),
                'columns' => [
                    [
                        'type' => 'text',
                        'name' => 'label',
                        'label' => Craft::t('formie', 'Label'),
                        'required' => true,
                    ],
                ],
            ]),
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Columns'),
                'instructions' => Craft::t('formie', 'Add, remove, or reorder likert scale columns.'),
                'name' => 'options',
                'if' => 'displayType == "likert" && optionsMode == "static"',
                '$field' => 'likertOptions',
                'enableOptionRowMenu' => true,
                'enableBulkOptions' => true,
                'predefinedOptions' => $this->getPredefinedOptions(),
                'bulkOptionsAction' => 'formie/fields/get-predefined-options',
                'newRowDefaults' => [
                    'points' => null,
                ],
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Enable Multiple Rows'),
                'instructions' => Craft::t('formie', 'Add row labels to collect multiple responses against the same scale.'),
                'name' => 'multipleRowsEnabled',
                'if' => 'displayType == "likert"',
            ]),
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Rows'),
                'instructions' => Craft::t('formie', 'Configure row labels for each statement or question. Scale columns are defined by your options source above.'),
                'name' => 'likertRows',
                'if' => 'displayType == "likert" && multipleRowsEnabled == true',
                'addRowLabel' => Craft::t('formie', 'Add a row'),
                'syncLikertRowValues' => true,
                'columns' => [
                    [
                        'type' => 'text',
                        'name' => 'label',
                        'label' => Craft::t('formie', 'Label'),
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
            SchemaHelper::emailFieldSummaryValue([
                'options' => [
                    ['label' => Craft::t('formie', 'Label'), 'value' => 'label'],
                    ['label' => Craft::t('formie', 'Value'), 'value' => 'value'],
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
        $layoutIf = 'displayType == "radio" || displayType == "checkboxes" || displayType == "likert" || displayType == "rank" || displayType == "rating"';

        return [
            SchemaHelper::visibility(),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
            SchemaHelper::errorMessagePosition($this),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Layout'),
                'instructions' => Craft::t('formie', 'Select which layout to use for these options.'),
                'name' => 'layout',
                'options' => [
                    ['label' => Craft::t('formie', 'Vertical'), 'value' => 'vertical'],
                    ['label' => Craft::t('formie', 'Horizontal'), 'value' => 'horizontal'],
                ],
                'if' => $layoutIf,
            ]),
            SchemaHelper::labelPosition($this, [
                'if' => $layoutIf,
            ]),
            SchemaHelper::colorField([
                'label' => Craft::t('formie', 'Star Color'),
                'instructions' => Craft::t('formie', 'Set the color of the rating stars.'),
                'name' => 'starColor',
                'if' => 'displayType == "rating"',
            ]),
        ];
    }

    public function defineFormBuilderAdvancedSchema(): array
    {
        return $this->defineQuestionAdvancedSchema();
    }

    public function defineFormBuilderConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    public function getResolvedOptions(): array
    {
        $options = parent::getResolvedOptions();

        if ($this->displayType === self::DISPLAY_LIKERT) {
            return $this->_getEffectiveLikertOptions($options);
        }

        if ($this->usesOptions()) {
            return $this->_getEffectiveQuestionOptions($options);
        }

        return $options;
    }

    public function getValidationOptionValues(): array
    {
        if (!$this->usesOptions()) {
            return parent::getValidationOptionValues();
        }

        $values = [];

        foreach ($this->getResolvedOptions() as $option) {
            if (!isset($option['optgroup'])) {
                $values[] = (string)$option['value'];
            }
        }

        return $values;
    }

    protected function _syncOptionValuesFromLabels(): void
    {
        if ($this->displayType === self::DISPLAY_LIKERT) {
            $this->_syncLikertColumnValues();

            return;
        }

        if ($this->usesOptions()) {
            $this->_syncQuestionOptionValuesFromLabels();
        }
    }

    public function beforeValidate(): bool
    {
        $this->_syncLabelFromQuestion();
        $this->_syncHandleFromQuestion();
        $this->_syncOptionValuesFromLabels();
        $this->_syncLikertRowValuesFromLabels();

        return parent::beforeValidate();
    }

    public function validateOptions(): void
    {
        if (!$this->usesOptions()) {
            return;
        }

        $this->_syncOptionValuesFromLabels();
        $this->_syncLikertRowValuesFromLabels();
        $this->_validateLikertRows();

        parent::validateOptions();
    }

    public function getInputTemplateVariables(Form $form, mixed $value): array
    {
        $variables = parent::getInputTemplateVariables($form, $value);

        if (!$this->usesOptions()) {
            if ($value instanceof SingleOptionFieldValue) {
                $variables['value'] = $value->value;
            }

            return $variables;
        }

        if ($this->displayType === self::DISPLAY_RANK) {
            $variables['fieldOptions'] = $this->_getRankFieldOptions($value);

            if ($value instanceof MultiOptionFieldValue) {
                $variables['value'] = $value->values();
            } elseif (!is_array($variables['value'])) {
                $variables['value'] = [];
            }

            return $variables;
        }

        if ($this->displayType !== self::DISPLAY_LIKERT) {
            return $variables;
        }

        $variables['likertRows'] = $this->getEffectiveLikertRows();
        $variables['usesLikertMultipleRows'] = $this->usesLikertMultipleRows();

        if ($this->usesLikertMultipleRows()) {
            if ($value instanceof LikertMultipleRowsFieldValue) {
                $variables['value'] = $value->toClientValue();
            } elseif (!is_array($variables['value'])) {
                $variables['value'] = [];
            }
        } elseif ($value instanceof SingleOptionFieldValue) {
            $variables['value'] = $value->value;
        }

        return $variables;
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if (!$this->usesOptions()) {
            $field = $this->getDisplayTypeField();

            if ($field instanceof FormieField) {
                return $field->normalizeValue($value, $element);
            }

            return parent::normalizeValue($value, $element);
        }

        if ($this->displayType !== self::DISPLAY_LIKERT || !$this->usesLikertMultipleRows()) {
            return parent::normalizeValue($value, $element);
        }

        if ($value instanceof LikertMultipleRowsFieldValue) {
            return $value;
        }

        if (is_string($value) && Json::isJsonObject($value)) {
            $value = Json::decodeIfJson($value);
        }

        if (!is_array($value)) {
            $value = [];
        }

        $effectiveRows = $this->getEffectiveLikertRows();
        $rowLabels = [];

        foreach ($effectiveRows as $row) {
            $rowLabels[(string)$row['value']] = (string)$row['label'];
        }

        $columnOptions = [];
        $columnLabelsByValue = [];

        foreach ($this->getResolvedOptions() as $option) {
            if (isset($option['optgroup'])) {
                continue;
            }

            $columnValue = (string)($option['value'] ?? '');
            $columnLabelsByValue[$columnValue] = (string)($option['label'] ?? '');
            $columnOptions[] = $columnValue;
        }

        $multipleRowsValue = new LikertMultipleRowsFieldValue([], $rowLabels);

        foreach ($effectiveRows as $row) {
            $rowKey = (string)$row['value'];
            $submitted = $value[$rowKey] ?? null;

            if ($submitted === null || $submitted === '') {
                continue;
            }

            if (is_array($submitted) && array_key_exists('value', $submitted)) {
                $submitted = $submitted['value'];
            }

            $columnValue = (string)$submitted;
            $valid = in_array($columnValue, $columnOptions, true);
            $columnLabel = $columnLabelsByValue[$columnValue] ?? $columnValue;

            if (!$this->usesStrictOptionValidation() && !$valid) {
                $columnLabel = $columnValue;
                $valid = true;
            }

            $multipleRowsValue->setSelection(
                $rowKey,
                new SingleOptionFieldValue($columnLabel, $columnValue, true, $valid),
            );
        }

        return $multipleRowsValue->isEmpty() ? null : $multipleRowsValue;
    }

    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if ($this->displayType === self::DISPLAY_LIKERT && $value instanceof LikertMultipleRowsFieldValue) {
            if (!$this->shouldPersistOptionLabels()) {
                return $value->toClientValue();
            }

            $serialized = [];

            foreach ($value->selections() as $rowKey => $selection) {
                $serialized[$rowKey] = [
                    'value' => $selection->value,
                    'label' => $selection->getDisplayLabel(),
                ];
            }

            return $serialized;
        }

        return parent::serializeValue($value, $element);
    }

    public function isValueEmpty(mixed $value, ?ElementInterface $element): bool
    {
        if (!$this->usesOptions()) {
            $field = $this->getDisplayTypeField();

            if ($field instanceof FormieField) {
                return $field->isValueEmpty($value, $element);
            }
        }

        if ($this->displayType === self::DISPLAY_LIKERT && $this->usesLikertMultipleRows()) {
            if (!($value instanceof LikertMultipleRowsFieldValue)) {
                return true;
            }

            $requiredRowKeys = array_map(
                static fn(array $row): string => (string)$row['value'],
                $this->getEffectiveLikertRows(),
            );

            return !$value->isComplete($requiredRowKeys);
        }

        return parent::isValueEmpty($value, $element);
    }

    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        if (!$this->usesOptions()) {
            $field = $this->getDisplayTypeField();

            if ($field instanceof FormieField) {
                return $field->getPreviewHtml($value, $element);
            }
        }

        if ($this->displayType === self::DISPLAY_LIKERT && $this->usesLikertMultipleRows()) {
            if (!($value instanceof LikertMultipleRowsFieldValue)) {
                return '';
            }

            $lines = [];

            foreach ($this->getEffectiveLikertRows() as $row) {
                $rowKey = (string)$row['value'];
                $selection = $value->getSelection($rowKey);

                if (!$selection || $selection->isEmpty()) {
                    continue;
                }

                $lines[] = Craft::t('formie', '{row}: {value}', [
                    'row' => $row['label'],
                    'value' => $selection->getDisplayLabel(),
                ]);
            }

            return $this->renderPreviewText(implode('<br>', $lines));
        }

        return parent::getPreviewHtml($value, $element);
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        if ($this->displayType === self::DISPLAY_LIKERT) {
            return Craft::$app->getView()->renderTemplate('formie/_formfields/likert/input', [
                'name' => $this->handle,
                'value' => $this->_normalizeSubmissionTemplateValue($value, true),
                'field' => $this,
                'fieldOptions' => $this->getFieldOptions(),
                'likertRows' => $this->getEffectiveLikertRows(),
                'usesLikertMultipleRows' => $this->usesLikertMultipleRows(),
            ]);
        }

        if (!$this->usesOptions()) {
            $field = $this->getDisplayTypeField();

            if ($field instanceof FormieField) {
                return $field->defineSubmissionHtml($value, $element, $inline);
            }
        }

        $presentationField = $this->getDisplayTypeField();

        if ($presentationField instanceof FormieField) {
            return $presentationField->defineSubmissionHtml(
                $this->_normalizeSubmissionTemplateValue($value),
                $element,
                $inline,
            );
        }

        $templateValue = $this->_normalizeSubmissionTemplateValue($value);

        return match ($this->displayType) {
            self::DISPLAY_RANK => Craft::$app->getView()->renderTemplate('formie/_formfields/rank/input', [
                'name' => $this->handle,
                'values' => $templateValue,
                'field' => $this,
                'fieldOptions' => $this->getFieldOptions(),
            ]),
            self::DISPLAY_RATING => Craft::$app->getView()->renderTemplate('formie/_formfields/rating/input', [
                'name' => $this->handle,
                'value' => $templateValue,
                'field' => $this,
                'fieldOptions' => $this->getFieldOptions(),
            ]),
            default => parent::defineSubmissionHtml($value, $element, $inline),
        };
    }

    protected function _normalizeSubmissionTemplateValue(mixed $value, bool $forLikert = false): mixed
    {
        if ($forLikert && $this->usesLikertMultipleRows()) {
            if ($value instanceof LikertMultipleRowsFieldValue) {
                return $value->toClientValue();
            }

            return is_array($value) ? $value : [];
        }

        if ($value instanceof SingleOptionFieldValue) {
            return $value->value;
        }

        if ($value instanceof MultiOptionFieldValue) {
            return $value->values();
        }

        if ($value instanceof LikertMultipleRowsFieldValue) {
            return $value->toClientValue();
        }

        return $value;
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        if (!$this->usesOptions()) {
            return array_values(array_filter($rules, static function(mixed $rule): bool {
                return !is_array($rule) || ($rule[1] ?? null) !== 'in';
            }));
        }

        if ($this->displayType === self::DISPLAY_LIKERT && $this->usesLikertMultipleRows()) {
            return array_values(array_filter($rules, static function(mixed $rule): bool {
                return !is_array($rule) || ($rule[1] ?? null) !== 'in';
            }));
        }

        return $rules;
    }

    public function getPresentationDisplayType(): string
    {
        return $this->displayType;
    }

    protected function defineClientModules(): array
    {
        $modules = parent::defineClientModules();

        if (in_array($this->displayType, [self::DISPLAY_LIKERT, self::DISPLAY_RANK, self::DISPLAY_RATING], true)) {
            foreach ($this->defineSurveyPresentationClientModules() as $module) {
                $modules[] = $module;
            }

            return $modules;
        }

        foreach ($this->definePresentationFieldClientModules() as $module) {
            $modules[] = $module;
        }

        return $modules;
    }

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        if ($tag = $this->_presentationFieldSlotTag($key, $context)) {
            return $tag;
        }

        return parent::defineFieldSlotTag($key, $context);
    }


    // Protected Methods
    // =========================================================================

    protected static function defaultDisplayOptions(string $displayType): ?array
    {
        $options = SurveyPresentationDefaults::resolveOptionsForDisplayType($displayType);

        return $options !== [] ? $options : null;
    }

    protected function optionsSettingLabel(): string
    {
        return Craft::t('formie', 'Survey Options');
    }

    protected function _applyPresentationDefaultOptions(string $previousDisplayType): void
    {
        if (!in_array($this->displayType, [self::DISPLAY_LIKERT, self::DISPLAY_RATING], true)) {
            return;
        }

        $defaults = static::defaultDisplayOptions($this->displayType);

        if ($defaults === null || !is_array($this->options) || $this->options !== []) {
            return;
        }

        $this->options = $defaults;
    }

    protected function supportedDefaults(): array
    {
        return [
            'displayType',
            'likertDefaultOptions',
            'ratingDefaultOptions',
        ];
    }

    protected function _syncScoringEnabledFromOptionsMode(): void
    {
        if (
            $this->displayType !== self::DISPLAY_LIKERT
            || $this->getOptionsMode() !== OptionsMode::STATIC
        ) {
            $this->scoringEnabled = false;
        }
    }

    protected function _syncLikertMultipleRowsSettings(): void
    {
        if ($this->displayType !== self::DISPLAY_LIKERT) {
            $this->multipleRowsEnabled = false;
        }
    }

    protected function _syncLikertRowValuesFromLabels(): void
    {
        if ($this->displayType !== self::DISPLAY_LIKERT || !is_array($this->likertRows)) {
            return;
        }

        $rows = $this->likertRows;
        $usedValues = [];

        foreach ($rows as $index => &$row) {
            if (!is_array($row)) {
                continue;
            }

            $label = trim((string)($row['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $value = $this->_resolveLikertKey(
                (string)($row['value'] ?? ''),
                $usedValues,
                'row',
            );

            $row['value'] = $value;
            $usedValues[] = $value;
        }
        unset($row);

        $this->likertRows = $rows;
    }

    protected function _syncLikertColumnValues(): void
    {
        if ($this->displayType !== self::DISPLAY_LIKERT || $this->getOptionsMode() !== OptionsMode::STATIC || !is_array($this->options)) {
            return;
        }

        $this->options = $this->_getEffectiveLikertOptions($this->options);
    }

    protected function _getEffectiveLikertOptions(array $options): array
    {
        $resolved = [];
        $usedValues = [];
        $columnIndex = 0;

        foreach ($options as $option) {
            if (!is_array($option) || !empty($option['optgroup'])) {
                $resolved[] = $option;
                continue;
            }

            $label = trim((string)($option['label'] ?? ''));

            if ($label === '') {
                $resolved[] = $option;
                continue;
            }

            $option['value'] = $this->_resolveLikertColumnKey(
                $columnIndex,
                (string)($option['value'] ?? ''),
                $usedValues,
            );
            $usedValues[] = $option['value'];
            $resolved[] = $option;
            $columnIndex++;
        }

        return $resolved;
    }

    protected function _createUniqueLikertKey(array $usedValues, string $prefix): string
    {
        do {
            $value = $prefix . '-' . StringHelper::randomString(10);
        } while (in_array($value, $usedValues, true));

        return $value;
    }

    protected function _isLikertOpaqueKey(string $value, string $prefix): bool
    {
        return (bool)preg_match('/^' . preg_quote($prefix, '/') . '-[A-Za-z0-9]{10}$/', $value);
    }

    protected function _deterministicLikertKey(int $index, array &$usedValues, string $prefix): string
    {
        $seed = (string)($this->uid ?? '') . ':' . $prefix . ':' . $index;
        $value = $prefix . '-' . substr(hash('sha256', $seed), 0, 10);

        while (in_array($value, $usedValues, true)) {
            $value = $this->_createUniqueLikertKey($usedValues, $prefix);
        }

        return $value;
    }

    protected function _resolveLikertColumnKey(int $index, string $storedValue, array &$usedValues): string
    {
        $storedValue = trim($storedValue);

        if ($storedValue !== '' && $this->_isLikertOpaqueKey($storedValue, 'col')) {
            return $storedValue;
        }

        if ($this->getOptionsMode() === OptionsMode::STATIC && $storedValue === '') {
            return $this->_createUniqueLikertKey($usedValues, 'col');
        }

        return $this->_deterministicLikertKey($index, $usedValues, 'col');
    }

    protected function _resolveLikertKey(
        string $value,
        array &$usedValues,
        string $prefix,
    ): string {
        $value = trim($value);

        if ($value !== '') {
            return $value;
        }

        return $this->_createUniqueLikertKey($usedValues, $prefix);
    }

    protected function _validateLikertRows(): void
    {
        if (!$this->multipleRowsEnabled || $this->displayType !== self::DISPLAY_LIKERT) {
            return;
        }

        $labels = [];
        $values = [];
        $hasDuplicateLabels = false;
        $hasDuplicateValues = false;

        foreach ($this->likertRows as &$row) {
            if (!is_array($row)) {
                continue;
            }

            $label = (string)($row['label'] ?? '');
            $value = (string)($row['value'] ?? '');

            if ($label === '' && $value === '') {
                continue;
            }

            if ($label !== '' && isset($labels[$label])) {
                $row['label'] = [
                    'value' => $label,
                    'hasErrors' => true,
                ];
                $hasDuplicateLabels = true;
            }

            if ($value !== '' && isset($values[$value])) {
                $row['value'] = [
                    'value' => $value,
                    'hasErrors' => true,
                ];
                $hasDuplicateValues = true;
            }

            if (is_string($row['label'])) {
                $labels[$label] = true;
            }

            if (is_string($row['value'])) {
                $values[$value] = true;
            }
        }
        unset($row);

        if ($hasDuplicateLabels) {
            $this->addError('likertRows', Craft::t('app', 'All option labels must be unique.'));
        }

        if ($hasDuplicateValues) {
            $this->addError('likertRows', Craft::t('app', 'All option values must be unique.'));
        }
    }

    protected function defineSurveyPresentationClientModules(): array
    {
        return match ($this->displayType) {
            self::DISPLAY_LIKERT => [
                new ClientModule([
                    'id' => 'survey-likert',
                    'renderTargets' => [ClientModule::RENDER_TARGET_FRONTEND],
                ]),
                new ClientModule([
                    'id' => 'checkbox-radio',
                    'renderTargets' => [ClientModule::RENDER_TARGET_FRONTEND],
                ]),
            ],
            self::DISPLAY_RANK => [
                new ClientModule([
                    'id' => 'survey-rank',
                    'renderTargets' => [ClientModule::RENDER_TARGET_FRONTEND],
                ]),
            ],
            self::DISPLAY_RATING => [
                new ClientModule([
                    'id' => 'survey-rating',
                    'renderTargets' => [ClientModule::RENDER_TARGET_FRONTEND],
                ]),
            ],
            default => [],
        };
    }

    private function _presentationFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        return match ($this->displayType) {
            self::DISPLAY_LIKERT => $this->_defineLikertSlotTag($key, $context),
            self::DISPLAY_RANK => $this->_defineRankSlotTag($key, $context),
            self::DISPLAY_RATING => $this->_defineRatingSlotTag($key, $context),
            default => null,
        };
    }

    private function _getLikertColumnHtmlId(Form $form, string $optionValue): string
    {
        return $this->getHtmlId($form, 'likert-col-' . $optionValue);
    }

    private function _getLikertMultipleRowsInputSuffix(array $contextArray): string
    {
        $likertRow = $contextArray['likertRow'] ?? null;
        $rowValue = is_array($likertRow) ? trim((string)($likertRow['value'] ?? '')) : '';
        $columnSuffix = $this->getFieldInputOptionValue($contextArray);

        if ($rowValue === '') {
            return $columnSuffix;
        }

        return $rowValue . '-' . $columnSuffix;
    }

    private function _defineLikertSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;
        $contextArray = $context->toArray();
        $option = $contextArray['option'] ?? null;
        $optionLabel = is_array($option) ? (string)($option['label'] ?? '') : '';

        if ($key === 'likertFieldLayout') {
            $core = [
                'data-formie-likert-field-layout' => true,
                'data-formie-radio-field-layout' => true,
            ];

            if ($this->usesLikertMultipleRows()) {
                $core['data-formie-likert-multiple-rows'] = true;
            }

            return SlotTag::make('table')
                ->core($core)
                ->theme([
                    'class' => [
                        'formie-likert-field-layout',
                    ],
                ]);
        }

        if ($key === 'fieldRowLabelHeader') {
            return SlotTag::make('th')
                ->core([
                    'scope' => 'col',
                    'role' => 'presentation',
                    'data-formie-likert-row-label-header' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-likert-row-label-header',
                    ],
                ]);
        }

        if ($key === 'fieldRowLabel') {
            $likertRow = $contextArray['likertRow'] ?? null;
            $rowLabel = is_array($likertRow) ? (string)($likertRow['label'] ?? '') : '';

            return SlotTag::make('th')
                ->core([
                    'scope' => 'row',
                    'data-formie-likert-row-label' => true,
                    'data-label' => $rowLabel ?: null,
                ])
                ->theme([
                    'class' => [
                        'formie-label',
                        'formie-likert-row-label',
                    ],
                ]);
        }

        if ($key === 'fieldColumnLabels') {
            return SlotTag::make('thead')
                ->core([
                    'data-formie-likert-column-labels' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-likert-column-labels',
                    ],
                ]);
        }

        if ($key === 'fieldColumnLabelsRow') {
            return SlotTag::make('tr')
                ->core([
                    'data-formie-likert-column-labels-row' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-likert-column-labels-row',
                    ],
                ]);
        }

        if ($key === 'fieldColumnLabel') {
            $optionValue = $this->getFieldInputOptionValue($contextArray);

            return SlotTag::make('th')
                ->core([
                    'id' => $this->_getLikertColumnHtmlId($form, $optionValue),
                    'scope' => 'col',
                    'role' => 'presentation',
                    'data-formie-likert-column-label' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-label',
                        'formie-likert-column-label',
                    ],
                ]);
        }

        if ($key === 'fieldInputs') {
            return SlotTag::make('tbody')
                ->core([
                    'data-formie-likert-inputs' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-likert-inputs',
                    ],
                ]);
        }

        if ($key === 'fieldInputsRow') {
            $classes = [
                'formie-likert-inputs-row',
            ];

            $likertRow = $contextArray['likertRow'] ?? null;
            $rowIndex = (int)($contextArray['likertRowIndex'] ?? 0);

            if (is_array($likertRow) && $rowIndex % 2 === 1) {
                $classes[] = 'formie-likert-inputs-row--alt';
            }

            return SlotTag::make('tr')
                ->core([
                    'data-formie-likert-inputs-row' => true,
                ])
                ->theme([
                    'class' => $classes,
                ]);
        }

        if ($key === 'fieldOption') {
            return SlotTag::make('td')
                ->core([
                    'data-formie-likert-option' => true,
                    'data-formie-field-option' => true,
                    'data-formie-radio-option' => true,
                    'data-label' => $optionLabel ?: null,
                ])
                ->theme([
                    'class' => [
                        'formie-field-option',
                        'formie-radio-option',
                        'formie-likert-option',
                    ],
                ]);
        }

        if ($key === 'fieldOptionLabel') {
            $inputSuffix = $this->_getLikertMultipleRowsInputSuffix($contextArray);

            return SlotTag::make('label')
                ->core([
                    'data-formie-field-option-label' => true,
                    'data-formie-likert-option-label' => true,
                    'data-formie-radio-option-label' => true,
                    'for' => $this->getHtmlId($form, $inputSuffix),
                    'aria-hidden' => 'true',
                ])
                ->theme([
                    'class' => [
                        'formie-field-option-label',
                        'formie-radio-option-label',
                        'formie-likert-option-label',
                    ],
                ]);
        }

        if ($key === 'fieldInput') {
            $inputSuffix = $this->_getLikertMultipleRowsInputSuffix($contextArray);
            $optionValue = $this->getFieldInputOptionValue($contextArray);
            $columnId = $this->_getLikertColumnHtmlId($form, $optionValue);
            $likertRow = $contextArray['likertRow'] ?? null;
            $rowValue = is_array($likertRow) ? trim((string)($likertRow['value'] ?? '')) : '';
            $name = $rowValue !== ''
                ? $this->getHtmlName($rowValue)
                : $this->getHtmlName();

            return SlotTag::make('input')
                ->core(array_merge([
                    'type' => 'radio',
                    'id' => $this->getHtmlId($form, $inputSuffix),
                    'name' => $name,
                    'required' => $this->required ? true : null,
                    'aria-labelledby' => $columnId,
                    'data-formie-input' => true,
                    'data-formie-radio-input' => true,
                    'data-formie-input-id' => $this->getHtmlDataId($form, $inputSuffix),
                    'data-formie-input-type' => 'radio',
                ], ValidationMessagesHelper::requiredClientAttributes($this)))
                ->theme([
                    'class' => [
                        'formie-input',
                        'formie-radio-input',
                    ],
                ])
                ->instanceAttributes($this->getInputAttributes());
        }

        return null;
    }

    /**
     * Rank fields submit every option in user-defined order. Re-render using that
     * order so validation failures do not reset the list back to field settings.
     */
    private function _getRankFieldOptions(mixed $value): array
    {
        $fieldOptions = $this->getFieldOptions();
        $fieldOptionsByValue = [];

        foreach ($fieldOptions as $option) {
            if (isset($option['optgroup'])) {
                continue;
            }

            $fieldOptionsByValue[(string)($option['value'] ?? '')] = $option;
        }

        $orderedValues = [];

        if ($value instanceof MultiOptionFieldValue) {
            $orderedValues = $value->values();
        } elseif (is_array($value)) {
            foreach ($value as $item) {
                if (is_array($item) && array_key_exists('value', $item)) {
                    $orderedValues[] = (string)$item['value'];
                } elseif (is_scalar($item) || $item === null) {
                    $orderedValues[] = (string)$item;
                }
            }
        }

        if ($orderedValues === []) {
            return $fieldOptions;
        }

        $orderedOptions = [];
        $appended = [];

        foreach ($orderedValues as $orderedValue) {
            $orderedValue = (string)$orderedValue;

            if (!isset($fieldOptionsByValue[$orderedValue]) || isset($appended[$orderedValue])) {
                continue;
            }

            $orderedOptions[] = $fieldOptionsByValue[$orderedValue];
            $appended[$orderedValue] = true;
        }

        foreach ($fieldOptions as $option) {
            if (isset($option['optgroup'])) {
                $orderedOptions[] = $option;
                continue;
            }

            $optionValue = (string)($option['value'] ?? '');

            if (!isset($appended[$optionValue])) {
                $orderedOptions[] = $option;
            }
        }

        return $orderedOptions;
    }

    private function _defineRankSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;

        if ($key === 'rankFieldLayout') {
            return SlotTag::make('div')
                ->core([
                    'data-formie-field-layout' => true,
                    'data-formie-rank-field-layout' => true,
                    'data-formie-survey-rank' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-field-layout',
                        'formie-rank-field-layout',
                    ],
                ]);
        }

        if ($key === 'fieldOptions') {
            return SlotTag::make('ul')
                ->core([
                    'data-formie-field-options' => true,
                    'data-formie-rank-options' => true,
                    'data-formie-survey-rank-list' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-field-options',
                        'formie-rank-options',
                        'formie-rank-list',
                    ],
                ]);
        }

        if ($key === 'fieldOption') {
            return SlotTag::make('li')
                ->core([
                    'data-formie-field-option' => true,
                    'data-formie-rank-option' => true,
                    'data-formie-survey-rank-item' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-field-option',
                        'formie-rank-option',
                        'formie-rank-item',
                    ],
                ]);
        }

        if ($key === 'fieldRankHandle') {
            return SlotTag::make('button')
                ->core([
                    'type' => 'button',
                    'tabindex' => '-1',
                    'data-formie-rank-handle' => true,
                    'aria-hidden' => 'true',
                ])
                ->theme([
                    'class' => [
                        'formie-rank-handle',
                    ],
                ]);
        }

        if ($key === 'fieldOptionLabel') {
            return SlotTag::make('span')
                ->core([
                    'data-formie-field-option-label' => true,
                    'data-formie-rank-option-label' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-field-option-label',
                        'formie-rank-option-label',
                    ],
                ]);
        }

        if ($key === 'fieldInput') {
            $optionValue = $this->getFieldInputOptionValue($context->toArray());

            return SlotTag::make('input')
                ->core([
                    'type' => 'hidden',
                    'name' => $this->getHtmlName('[]'),
                    'value' => $optionValue ?? false,
                    'data-formie-input' => true,
                    'data-formie-rank-input' => true,
                    'data-formie-input-id' => $this->getHtmlDataId($form, $optionValue),
                    'data-formie-input-type' => 'hidden',
                ])
                ->theme([
                    'class' => [
                        'formie-input',
                        'formie-rank-input',
                    ],
                ]);
        }

        return null;
    }

    private function _defineRatingSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;

        if ($key === 'ratingFieldLayout') {
            return SlotTag::make('div')
                ->core([
                    'data-formie-field-layout' => true,
                    'data-formie-rating-field-layout' => true,
                    'data-formie-survey-rating' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-field-layout',
                        'formie-rating-field-layout',
                    ],
                ]);
        }

        if ($key === 'fieldOptions') {
            $theme = [
                'class' => [
                    'formie-field-options',
                    'formie-rating-options',
                    'formie-rating-stars',
                ],
            ];

            if ($this->starColor) {
                $theme['style'] = $this->_ratingStarCssVars($this->starColor);
            }

            return SlotTag::make('div')
                ->core([
                    'data-formie-field-options' => true,
                    'data-formie-rating-options' => true,
                    'data-formie-survey-rating-stars' => true,
                    'role' => 'radiogroup',
                ])
                ->theme($theme);
        }

        if ($key === 'fieldOption') {
            return SlotTag::make('div')
                ->core([
                    'data-formie-field-option' => true,
                    'data-formie-rating-option' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-field-option',
                        'formie-rating-option',
                    ],
                ]);
        }

        if ($key === 'fieldInput') {
            $optionValue = $this->getFieldInputOptionValue($context->toArray());

            return SlotTag::make('input')
                ->core(array_merge([
                    'type' => 'radio',
                    'id' => $this->getHtmlId($form, $optionValue),
                    'name' => $this->getHtmlName(),
                    'required' => $this->required ? true : null,
                    'data-formie-input' => true,
                    'data-formie-rating-input' => true,
                    'data-formie-input-id' => $this->getHtmlDataId($form, $optionValue),
                    'data-formie-input-type' => 'radio',
                ], ValidationMessagesHelper::requiredClientAttributes($this)))
                ->theme([
                    'class' => [
                        'formie-input',
                        'formie-rating-input',
                    ],
                ])
                ->instanceAttributes($this->getInputAttributes());
        }

        if ($key === 'fieldOptionLabel') {
            $optionValue = $this->getFieldInputOptionValue($context->toArray());

            return SlotTag::make('label')
                ->core([
                    'data-formie-field-option-label' => true,
                    'data-formie-rating-option-label' => true,
                    'for' => $this->getHtmlId($form, $optionValue),
                ])
                ->theme([
                    'class' => [
                        'formie-field-option-label',
                        'formie-rating-option-label',
                    ],
                ]);
        }

        return null;
    }

    private function _ratingStarCssVars(string $starColor): array
    {
        return [
            '--formie-survey-rating-star-outline' => $this->_ratingStarOutlineUrl($starColor),
            '--formie-survey-rating-star-filled' => $this->_ratingStarFilledUrl($starColor),
        ];
    }

    private function _ratingStarOutlineUrl(string $color): string
    {
        $fill = str_replace('#', '%23', $color);

        return 'url("data:image/svg+xml;charset=utf8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2024%2023%22%20fill%3D%22none%22%3E%3Cpath%20d%3D%22M22.2%208.05333L15.7466%207.49333L13.2266%201.56C12.7733%200.48%2011.2266%200.48%2010.7733%201.56L8.25331%207.50667L1.81331%208.05333C0.639975%208.14667%200.159975%209.61333%201.05331%2010.3867L5.94664%2014.6267L4.47998%2020.92C4.21331%2022.0667%205.45331%2022.9733%206.46664%2022.36L12%2019.0267L17.5333%2022.3733C18.5466%2022.9867%2019.7866%2022.08%2019.52%2020.9333L18.0533%2014.6267L22.9466%2010.3867C23.84%209.61333%2023.3733%208.14667%2022.2%208.05333ZM12%2016.5333L6.98664%2019.56L8.31998%2013.8533L3.89331%2010.0133L9.73331%209.50667L12%204.13333L14.28%209.52L20.12%2010.0267L15.6933%2013.8667L17.0266%2019.5733L12%2016.5333Z%22%20fill%3D%22' . $fill . '%22%2F%3E%3C%2Fsvg%3E")';
    }

    private function _ratingStarFilledUrl(string $color): string
    {
        $fill = str_replace('#', '%23', $color);

        return 'url("data:image/svg+xml;charset=utf8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2024%2023%22%20fill%3D%22none%22%3E%3Cpath%20d%3D%22M12%2019.0267L17.5333%2022.3733C18.5466%2022.9867%2019.7866%2022.08%2019.52%2020.9333L18.0533%2014.64L22.9466%2010.4C23.84%209.62667%2023.36%208.16%2022.1866%208.06667L15.7466%207.52L13.2266%201.57334C12.7733%200.493336%2011.2266%200.493336%2010.7733%201.57334L8.25331%207.50667L1.81331%208.05334C0.639975%208.14667%200.159975%209.61334%201.05331%2010.3867L5.94664%2014.6267L4.47997%2020.92C4.21331%2022.0667%205.45331%2022.9733%206.46664%2022.36L12%2019.0267Z%22%20fill%3D%22' . $fill . '%22%2F%3E%3C%2Fsvg%3E")';
    }
}
