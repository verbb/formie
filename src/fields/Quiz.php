<?php
namespace verbb\formie\fields;

use verbb\formie\base\DisplayTypeFieldInterface;
use verbb\formie\base\Field as FormieField;
use verbb\formie\base\OptionsField;
use verbb\formie\base\QuestionnaireFieldInterface;
use verbb\formie\base\SortableFieldInterface;
use verbb\formie\fields\traits\DisplayTypeFieldTrait;
use verbb\formie\fields\traits\QuestionFieldTrait;
use verbb\formie\fields\values\MultiOptionFieldValue;
use verbb\formie\fields\values\SingleOptionFieldValue;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\RichText;

use Craft;
use craft\base\ElementInterface;

class Quiz extends OptionsField implements SortableFieldInterface, QuestionnaireFieldInterface, DisplayTypeFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Quiz');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/quiz/icon.svg';
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

    public const FIELD_TYPE_DROPDOWN = 'dropdown';
    public const FIELD_TYPE_RADIO = 'radio';
    public const FIELD_TYPE_CHECKBOXES = 'checkboxes';


    // Traits
    // =========================================================================

    use QuestionFieldTrait;
    use DisplayTypeFieldTrait;


    // Properties
    // =========================================================================

    public RichText $question;
    public string $fieldType = self::FIELD_TYPE_RADIO;
    public bool $randomizeOptions = false;
    public bool $enableAnswerExplanation = false;
    public RichText $answerExplanation;
    public bool $weightedScoreEnabled = false;
    public ?string $layout = 'vertical';


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        $this->_initQuestionFieldConfig($config);
        $this->initDisplayTypeFieldConfig($config);
        $config['answerExplanation'] = RichText::from($config['answerExplanation'] ?? null);

        parent::__construct($config);

        $this->multi = $this->fieldType === self::FIELD_TYPE_CHECKBOXES;
    }

    public function setAttributes($values, $safeOnly = true): void
    {
        if (is_array($values)) {
            if (array_key_exists('answerExplanation', $values)) {
                $values['answerExplanation'] = RichText::from($values['answerExplanation']);
            }

            if (array_key_exists('fieldType', $values)) {
                $this->multi = ($values['fieldType'] ?? $this->fieldType) === self::FIELD_TYPE_CHECKBOXES;
            }
        }

        parent::setAttributes($values, $safeOnly);

        $this->multi = $this->fieldType === self::FIELD_TYPE_CHECKBOXES;
    }

    public function getSettings(): array
    {
        $settings = parent::getSettings();
        $settings['question'] = $this->question->getSchema();
        $settings['answerExplanation'] = $this->answerExplanation->getSchema();

        return $settings;
    }

    public function settingsAttributes(): array
    {
        return array_merge(parent::settingsAttributes(), $this->defineDisplayTypeSettingsAttributes(), [
            'fieldType',
            'randomizeOptions',
            'enableAnswerExplanation',
            'answerExplanation',
            'weightedScoreEnabled',
        ]);
    }

    public function themeConfigKey(): string
    {
        return match ($this->fieldType) {
            self::FIELD_TYPE_CHECKBOXES => 'checkboxes',
            self::FIELD_TYPE_DROPDOWN => 'dropdown',
            default => 'radioButtons',
        };
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewChoiceList('checkbox', [
                'if' => 'field.fieldType == "checkboxes"',
            ]),
            SchemaHelper::previewSelect([
                'if' => 'field.fieldType == "dropdown"',
            ]),
            SchemaHelper::previewChoiceList('radio', [
                'if' => 'field.fieldType == "radio"',
            ]),
        ];
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            ...$this->defineQuestionGeneralSchema(),
            ...$this->defineLockedFieldTypeSchema('fieldType', [
                ['label' => Craft::t('formie', 'Dropdown'), 'value' => self::FIELD_TYPE_DROPDOWN],
                ['label' => Craft::t('formie', 'Radio Buttons'), 'value' => self::FIELD_TYPE_RADIO],
                ['label' => Craft::t('formie', 'Checkboxes'), 'value' => self::FIELD_TYPE_CHECKBOXES],
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Randomize Options'),
                'instructions' => Craft::t('formie', 'Randomize the order in which options are displayed on the front-end. This does not affect stored submission values.'),
                'name' => 'randomizeOptions',
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Enable Answer Explanation'),
                'instructions' => Craft::t('formie', 'Provide an explanation for the correct answer, shown when displaying quiz results.'),
                'name' => 'enableAnswerExplanation',
            ]),
            SchemaHelper::richTextField(array_merge([
                'label' => Craft::t('formie', 'Answer Explanation'),
                'instructions' => Craft::t('formie', 'Explain why the correct answer is correct.'),
                'name' => 'answerExplanation',
                'if' => 'enableAnswerExplanation',
            ], RichTextHelper::getRichTextConfig('fields.question'))),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Weighted Score'),
                'instructions' => Craft::t('formie', 'Award different point values per option. Higher scores should be assigned to correct answers.'),
                'name' => 'weightedScoreEnabled',
            ]),
            ...$this->defineOptionDynamicGeneralSchema(),
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Static Options'),
                'instructions' => Craft::t('formie', 'Add, remove, or reorder options. Mark correct answers and optionally assign point values.'),
                'name' => 'options',
                'if' => 'optionsMode == "static"',
                '$field' => 'quizOptions',
                'enableOptionRowMenu' => true,
                'enableBulkOptions' => true,
                'predefinedOptions' => $this->getPredefinedOptions(),
                'bulkOptionsAction' => 'formie/fields/get-predefined-options',
                'newRowDefaults' => [
                    'default' => false,
                    'isCorrect' => false,
                    'points' => null,
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
        $layoutIf = 'fieldType == "radio" || fieldType == "checkboxes"';

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

    public function beforeValidate(): bool
    {
        $this->_syncLabelFromQuestion();
        $this->_syncHandleFromQuestion();
        $this->_syncOptionValuesFromLabels();

        return parent::beforeValidate();
    }

    public function validateOptions(): void
    {
        $this->_syncOptionValuesFromLabels();

        parent::validateOptions();
    }

    public function getResolvedOptions(): array
    {
        $options = parent::getResolvedOptions();
        $options = $this->_getEffectiveQuestionOptions($options);

        if (!$this->randomizeOptions || count($options) <= 1) {
            return $options;
        }

        $groups = [];
        $currentGroup = [];

        foreach ($options as $option) {
            if (isset($option['optgroup'])) {
                if ($currentGroup) {
                    $groups[] = $currentGroup;
                    $currentGroup = [];
                }

                $groups[] = [$option];
                continue;
            }

            $currentGroup[] = $option;
        }

        if ($currentGroup) {
            $groups[] = $currentGroup;
        }

        $resolved = [];

        foreach ($groups as $group) {
            if (count($group) === 1 && isset($group[0]['optgroup'])) {
                $resolved[] = $group[0];
                continue;
            }

            shuffle($group);
            array_push($resolved, ...$group);
        }

        return $resolved;
    }

    public function getValidationOptionValues(): array
    {
        $values = [];

        foreach ($this->getResolvedOptions() as $option) {
            if (!isset($option['optgroup'])) {
                $values[] = (string)$option['value'];
            }
        }

        return $values;
    }

    public function defineClientInput(): array
    {
        $contract = parent::defineClientInput();
        $contract['randomizeOptions'] = $this->randomizeOptions;

        return $contract;
    }

    public function getPresentationDisplayType(): string
    {
        return $this->fieldType;
    }

    protected function defineClientModules(): array
    {
        return array_merge(
            parent::defineClientModules(),
            $this->definePresentationFieldClientModules(),
        );
    }


    // Protected Methods
    // =========================================================================

    protected function optionsSettingLabel(): string
    {
        return Craft::t('formie', 'Quiz Options');
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $presentationField = $this->getDisplayTypeField();

        if ($presentationField instanceof FormieField) {
            return $presentationField->defineSubmissionHtml(
                $this->_normalizeSubmissionTemplateValue($value),
                $element,
                $inline,
            );
        }

        return parent::defineSubmissionHtml($value, $element, $inline);
    }

    protected function _normalizeSubmissionTemplateValue(mixed $value): mixed
    {
        if ($value instanceof SingleOptionFieldValue) {
            return $value->value;
        }

        if ($value instanceof MultiOptionFieldValue) {
            return $value->values();
        }

        return $value;
    }
}
