<?php
namespace verbb\formie\integrations\captchas;

use verbb\formie\base\Captcha;
use verbb\formie\base\FormInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\FieldLayoutPage;

use Craft;

class Question extends Captcha
{
    private const CAPTCHA_PARAM = 'formieCaptchaQuestion';

    // Properties
    // =========================================================================

    public ?string $handle = 'question';
    public array $questions = [];


    // Public Methods
    // =========================================================================

    public function getName(): string
    {
        return Craft::t('formie', 'Question');
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Presents a simple security question for human verification.');
    }

    public function renderHtml(Form $form, FieldLayoutPage $page = null): string
    {
        $questions = $this->_getConfiguredQuestions();

        if (!$questions) {
            return '';
        }

        $questionIndex = $this->_getSelectedQuestionIndex($questions);
        $question = $questions[$questionIndex]['question'] ?? '';
        $submission = $form->getCurrentSubmission();
        $payload = Craft::$app->getRequest()->getParam(self::CAPTCHA_PARAM);

        $field = new SingleLineText();
        $field->handle = self::CAPTCHA_PARAM;
        $field->label = $question;
        $field->required = true;
        $field->errorMessage = Craft::t('formie', 'Please answer the security question.');

        return $form->renderTemplate('integrations/captchas/question/field', [
            'form' => $form,
            'field' => $field,
            'submission' => $submission,
            'errors' => $submission?->getErrors(self::CAPTCHA_PARAM) ?? [],
            'questionIndex' => $questionIndex,
            'answerValue' => is_array($payload) ? (string)($payload['answer'] ?? '') : '',
        ]);
    }

    public function getRefreshJsVariables(Form $form, $page = null): array
    {
        return [];
    }

    public function getGqlVariables(Form $form, FieldLayoutPage $page = null): array
    {
        return $this->getRefreshJsVariables($form, $page);
    }

    public function hasStrictValidation(): bool
    {
        return true;
    }

    public function validateSubmission(Submission $submission): bool
    {
        // If there are no questions, just disable the captcha, it's not setup right.
        $questions = $this->_getConfiguredQuestions();

        if (!$questions) {
            return true;
        }

        [$index, $rawAnswer] = $this->_getSubmittedAnswer($submission);

        if ($index === null || !isset($questions[$index])) {
            $submission->addError(self::CAPTCHA_PARAM, Craft::t('formie', 'Invalid question.'));
            return false;
        }

        if (!is_string($rawAnswer) || trim($rawAnswer) === '') {
            $submission->addError(self::CAPTCHA_PARAM, Craft::t('formie', 'Incorrect answer. Please try again.'));
            return false;
        }

        $normalizedAnswer = $this->_normalizeAnswer($rawAnswer);
        $validAnswers = array_values(array_filter(array_map(
            fn(string $answer) => $this->_normalizeAnswer($answer),
            explode(',', (string)($questions[$index]['answers'] ?? ''))
        )));

        if (!in_array($normalizedAnswer, $validAnswers, true)) {
            $submission->addError(self::CAPTCHA_PARAM, Craft::t('formie', 'Incorrect answer. Please try again.'));
            return false;
        }

        return true;
    }


    // Protected Methods
    // =========================================================================

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);
        $schema[] = SchemaHelper::tableField([
            'label' => Craft::t('formie', 'Security Questions'),
            'instructions' => Craft::t('formie', 'Define one or more questions to show randomly. Each question must include at least one accepted answer.'),
            'name' => 'questions',
            'columns' => [
                ['name' => 'question', 'label' => Craft::t('formie', 'Question'), 'type' => 'text'],
                ['name' => 'answers', 'label' => Craft::t('formie', 'Answers (comma-separated)'), 'type' => 'text'],
            ],
        ]);

        return $schema;
    }


    // Private Methods
    // =========================================================================

    private function _normalizeAnswer(string $value): string
    {
        $value = trim(strtolower($value));
        $value = preg_replace('/[^\p{L}\p{N}]/u', '', $value); // remove punctuation/symbols

        return $value;
    }

    private function _getConfiguredQuestions(): array
    {
        return array_values(array_filter($this->questions, function(array $question): bool {
            return trim((string)($question['question'] ?? '')) !== '' && trim((string)($question['answers'] ?? '')) !== '';
        }));
    }

    private function _getSelectedQuestionIndex(array $questions): int
    {
        $payload = Craft::$app->getRequest()->getParam(self::CAPTCHA_PARAM);
        $submittedIndex = is_array($payload) ? ($payload['index'] ?? null) : null;

        if ($submittedIndex !== null && isset($questions[(int)$submittedIndex])) {
            return (int)$submittedIndex;
        }

        return random_int(0, count($questions) - 1);
    }

    private function _getSubmittedAnswer(Submission $submission): array
    {
        $payload = $this->getCaptchaValue($submission, self::CAPTCHA_PARAM);

        if (is_array($payload)) {
            $index = $payload['index'] ?? null;
            $answer = $payload['answer'] ?? null;

            return [is_numeric($index) ? (int)$index : null, $answer];
        }

        // Support the older pseudo-field payload shape while existing forms transition.
        $legacyAnswers = $this->getCaptchaValue($submission, 'fields[' . self::CAPTCHA_PARAM . ']');

        if (!is_array($legacyAnswers) || !$legacyAnswers) {
            return [null, null];
        }

        $index = array_key_first($legacyAnswers);

        return [is_numeric($index) ? (int)$index : null, $legacyAnswers[$index] ?? null];
    }
}
