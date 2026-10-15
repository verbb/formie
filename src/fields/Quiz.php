<?php
namespace verbb\formie\fields;

use verbb\formie\base\DisplayTypeFieldInterface;
use verbb\formie\base\Field as FormieField;
use verbb\formie\base\OptionsField;
use verbb\formie\base\QuestionnaireFieldInterface;
use verbb\formie\base\SortableFieldInterface;
use verbb\formie\compatibility\fields\FieldConfigNormalizer;
use verbb\formie\fields\traits\DisplayTypeFieldTrait;
use verbb\formie\fields\traits\QuestionFieldTrait;
use verbb\formie\fields\values\MultiOptionFieldValue;
use verbb\formie\fields\values\SingleOptionFieldValue;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\RichText;
use verbb\formie\models\SlotTag;
use verbb\formie\theme\context\RenderContext;

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

    public static function translatableProperties(): array
    {
        $properties = parent::translatableProperties();
        $properties[] = 'question';
        $properties[] = 'answerExplanation';

        return $properties;
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/quiz/icon.svg';
    }

    /** @deprecated Use valueType() for the normalized runtime contract. */
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

    public const DISPLAY_DROPDOWN = 'dropdown';
    public const DISPLAY_RADIO = 'radio';
    public const DISPLAY_CHECKBOXES = 'checkboxes';


    // Traits
    // =========================================================================

    use QuestionFieldTrait;
    use DisplayTypeFieldTrait;


    // Properties
    // =========================================================================

    public RichText $question;
    public string $displayType = self::DISPLAY_RADIO;
    public bool $randomizeOptions = false;
    public bool $enableAnswerExplanation = false;
    public RichText $answerExplanation;
    public bool $weightedScoreEnabled = false;
    public ?string $layout = 'vertical';


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        $config = FieldConfigNormalizer::normalizeSettingAliases($config, static::class);
        $this->_initQuestionFieldConfig($config);
        $this->initDisplayTypeFieldConfig($config);
        $config['answerExplanation'] = RichText::from($config['answerExplanation'] ?? null);

        parent::__construct($config);

        $this->multi = $this->displayType === self::DISPLAY_CHECKBOXES;
    }

    public function setAttributes($values, $safeOnly = true): void
    {
        if (is_array($values)) {
            if (array_key_exists('answerExplanation', $values)) {
                $values['answerExplanation'] = RichText::from($values['answerExplanation']);
            }

            if (array_key_exists('displayType', $values)) {
                $this->multi = ($values['displayType'] ?? $this->displayType) === self::DISPLAY_CHECKBOXES;
            }
        }

        parent::setAttributes($values, $safeOnly);

        $this->multi = $this->displayType === self::DISPLAY_CHECKBOXES;
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
            'displayType',
            'randomizeOptions',
            'enableAnswerExplanation',
            'answerExplanation',
            'weightedScoreEnabled',
        ]);
    }

    public function themeConfigKey(): string
    {
        return match ($this->displayType) {
            self::DISPLAY_CHECKBOXES => 'checkboxes',
            self::DISPLAY_DROPDOWN => 'dropdown',
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
            SchemaHelper::previewChoiceList('radio', [
                'if' => 'field.displayType == "radio"',
            ]),
        ];
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            ...$this->defineQuestionGeneralSchema(),
            ...$this->defineLockedFieldTypeSchema('displayType', [
                ['label' => Craft::t('formie', 'Dropdown'), 'value' => self::DISPLAY_DROPDOWN],
                ['label' => Craft::t('formie', 'Radio Buttons'), 'value' => self::DISPLAY_RADIO],
                ['label' => Craft::t('formie', 'Checkboxes'), 'value' => self::DISPLAY_CHECKBOXES],
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
                // Validation index (React still owns the visible column set / weighted Score).
                'columns' => [
                    [
                        'type' => 'text',
                        'name' => 'label',
                        'label' => Craft::t('formie', 'Label'),
                        'required' => true,
                    ],
                    [
                        'type' => 'checkbox',
                        'name' => 'isCorrect',
                        'label' => Craft::t('formie', 'Correct'),
                    ],
                ],
            ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::prefillQueryParam(),
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
        $layoutIf = 'displayType == "radio" || displayType == "checkboxes"';

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

    public function getPresentationDisplayType(): string
    {
        return $this->displayType;
    }


    // Protected Methods
    // =========================================================================

    protected function defineClientRenderedInput(): array
    {
        $contract = parent::defineClientRenderedInput();
        $contract['randomizeOptions'] = $this->randomizeOptions;

        return $contract;
    }

    protected function defineSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        return $this->definePresentationFieldSlotTag($key, $context) ?? parent::defineSlotTag($key, $context);
    }

    protected function defineBrowserModules(): array
    {
        $modules = parent::defineBrowserModules();

        foreach ($this->definePresentationFieldBrowserModules() as $module) {
            $modules[] = $module;
        }

        return $modules;
    }

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
