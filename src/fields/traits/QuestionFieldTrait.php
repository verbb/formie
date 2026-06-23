<?php
namespace verbb\formie\fields\traits;

use verbb\formie\elements\Submission;
use verbb\formie\helpers\OptionsMode;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\RichText;
use verbb\formie\positions\AboveInput as AboveInputPosition;

use Craft;
use craft\helpers\StringHelper;
use craft\helpers\Template;

use Twig\Markup;

trait QuestionFieldTrait
{
    // Constants
    // =========================================================================

    private const QUESTION_LABEL_MAX_LENGTH = 80;


    // Static Methods
    // =========================================================================

    public static function defineQuestionFieldTypeConfig(): array
    {
        $config = [
            'hasLabel' => false,
            'labelSource' => 'question',
        ];

        $resultsWhen = static::defineQuestionnaireResultsWhen();

        if ($resultsWhen !== null) {
            $config['questionnaireResultsWhen'] = $resultsWhen;
        }

        return $config;
    }

    public static function defineQuestionnaireResultsWhen(): ?array
    {
        return null;
    }


    // Public Methods
    // =========================================================================

    public function supportsQuestionnaireResults(): bool
    {
        return true;
    }

    public function setAttributes($values, $safeOnly = true): void
    {
        if (is_array($values) && array_key_exists('question', $values)) {
            $values['question'] = RichText::from($values['question']);
        }

        parent::setAttributes($values, $safeOnly);
    }

    public function getSettings(): array
    {
        $settings = parent::getSettings();
        $settings['question'] = $this->question->getSchema();

        return $settings;
    }

    public function hasLabel(): bool
    {
        return true;
    }

    public function usesQuestionLabel(): bool
    {
        return true;
    }

    public function getQuestionPlainText(?Submission $submission = null): string
    {
        if (!$this->question->isEmpty()) {
            return trim($this->question->toPlainText($submission));
        }

        return trim((string)$this->label);
    }

    public function getQuestionHtml(?Submission $submission = null): Markup
    {
        if ($this->question->isEmpty()) {
            $label = trim((string)$this->label);

            return $label !== ''
                ? Template::raw($label)
                : Template::raw('');
        }

        $html = RichTextHelper::getHtmlContent($this->question, $submission, false);

        return Template::raw($html);
    }

    public function getLabelHtml(): Markup
    {
        return $this->getQuestionHtml();
    }

    public function getClientPayload(): array
    {
        $payload = parent::getClientPayload();
        $html = $this->getQuestionHtml()->__toString();

        if ($html !== '') {
            $payload['label'] = $html;
        }

        return $payload;
    }

    public function validateQuestion(): void
    {
        if ($this->question->isEmpty() && trim((string)$this->label) === '') {
            $this->addError('question', Craft::t('formie', 'Question cannot be blank.'));
        }
    }
    

    // Protected Methods
    // =========================================================================

    protected function defineQuestionGeneralSchema(): array
    {
        return [
            SchemaHelper::richTextField(array_merge([
                'label' => Craft::t('formie', 'Question'),
                'instructions' => Craft::t('formie', 'The question shown to respondents. Supports basic formatting and links.'),
                'name' => 'question',
                'validation' => 'requiredRichText',
                'required' => true,
            ], RichTextHelper::getRichTextConfig('fields.question'))),
            ...$this->defineHiddenQuestionHandleSchema(),
        ];
    }

    protected function defineHiddenQuestionHandleSchema(): array
    {
        return [
            SchemaHelper::handleField([
                'source' => 'question',
                'if' => '0 == 1',
                'instructions' => '',
                'warning' => null,
            ]),
        ];
    }

    protected function defineQuestionAdvancedSchema(): array
    {
        return [
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
            SchemaHelper::inputAttributesField(),
        ];
    }

    protected function defineLockedFieldTypeSchema(string $name, array $options, ?string $fieldComponent = null): array
    {
        $lockedWarning = Craft::t('formie', 'This cannot be changed after submissions have been received, to prevent data loss.');
        $instructions = Craft::t('formie', 'Select how this question is displayed on the front-end form.');

        $editableField = [
            'label' => Craft::t('formie', 'Field Type'),
            'instructions' => $instructions,
            'name' => $name,
            'options' => $options,
            'if' => '!formBuilder.hasSubmissions || !formBuilder.fieldIsPersisted',
        ];

        if ($fieldComponent) {
            $editableField['$field'] = $fieldComponent;
        }

        return [
            SchemaHelper::selectField($editableField),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Field Type'),
                'instructions' => $instructions,
                'name' => $name,
                'options' => $options,
                'disabled' => true,
                'if' => 'formBuilder.hasSubmissions && formBuilder.fieldIsPersisted',
            ]),
            SchemaHelper::groupField([
                'warning' => $lockedWarning,
                'if' => 'formBuilder.hasSubmissions && formBuilder.fieldIsPersisted',
            ]),
        ];
    }

    protected function _syncLabelFromQuestion(): void
    {
        $plainText = $this->getQuestionPlainText();

        if ($plainText === '') {
            return;
        }

        $this->label = StringHelper::truncate($plainText, self::QUESTION_LABEL_MAX_LENGTH);
    }

    protected function _syncHandleFromQuestion(): void
    {
        if ($this->id) {
            return;
        }

        $plainText = $this->getQuestionPlainText();

        if ($plainText === '') {
            return;
        }

        if (trim((string)$this->handle) !== '') {
            return;
        }

        $handle = StringHelper::toHandle($plainText);

        if ($handle !== '') {
            $this->handle = $handle;
        }
    }

    protected function _syncOptionValuesFromLabels(): void
    {
        if ($this->getOptionsMode() !== OptionsMode::STATIC || !is_array($this->options)) {
            return;
        }

        $this->options = $this->_getEffectiveQuestionOptions($this->options);
    }

    protected function _getEffectiveQuestionOptions(array $options): array
    {
        $resolved = [];
        $usedValues = [];
        $optionIndex = 0;

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

            $storedValue = trim((string)($option['value'] ?? ''));

            if (
                $this->getOptionsMode() === OptionsMode::STATIC
                && $storedValue !== ''
                && !$this->_isQuestionOptionOpaqueKey($storedValue)
            ) {
                $usedValues[] = $storedValue;
                $resolved[] = $option;
                $optionIndex++;
                continue;
            }

            $option['value'] = $this->_resolveQuestionOptionKey(
                $optionIndex,
                $storedValue,
                $usedValues,
            );
            $usedValues[] = $option['value'];
            $resolved[] = $option;
            $optionIndex++;
        }

        return $resolved;
    }

    protected function _resolveQuestionOptionKey(int $index, string $storedValue, array &$usedValues): string
    {
        $storedValue = trim($storedValue);

        if ($storedValue !== '' && $this->_isQuestionOptionOpaqueKey($storedValue)) {
            return $storedValue;
        }

        if ($this->getOptionsMode() === OptionsMode::STATIC && $storedValue === '') {
            return $this->_createUniqueQuestionOptionKey($usedValues);
        }

        return $this->_deterministicQuestionOptionKey($index, $usedValues);
    }

    protected function _createUniqueQuestionOptionKey(array &$usedValues): string
    {
        do {
            $value = 'opt-' . StringHelper::randomString(10);
        } while (in_array($value, $usedValues, true));

        return $value;
    }

    protected function _deterministicQuestionOptionKey(int $index, array &$usedValues): string
    {
        $seed = (string)($this->uid ?? '') . ':opt:' . $index;
        $value = 'opt-' . substr(hash('sha256', $seed), 0, 10);

        while (in_array($value, $usedValues, true)) {
            $value = $this->_createUniqueQuestionOptionKey($usedValues);
        }

        return $value;
    }

    protected function _isQuestionOptionOpaqueKey(string $value): bool
    {
        return (bool)preg_match('/^opt-[A-Za-z0-9]{10}$/', $value);
    }

    protected function _initQuestionFieldConfig(array &$config): void
    {
        $config['labelPosition'] = $config['labelPosition'] ?? AboveInputPosition::class;
        $config['question'] = RichText::from($config['question'] ?? null);
    }
}
