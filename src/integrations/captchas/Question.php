<?php
namespace verbb\formie\integrations\captchas;

use verbb\formie\Formie;
use verbb\formie\base\Captcha;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\Html;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\FieldLayoutRow;

use Craft;

class Question extends Captcha
{
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

    public function getFormSettingsHtml($form): string
    {
        return Craft::$app->getView()->renderTemplate('formie/integrations/captchas/question/_form-settings', [
            'integration' => $this,
            'form' => $form,
        ]);
    }

    public function getFrontEndHtml(Form $form, FieldLayoutPage $page = null): string
    {
        if (empty($this->questions)) {
            return '';
        }

        $questionIndex = random_int(0, count($this->questions) - 1);
        $question = $this->questions[$questionIndex]['question'] ?? '';

        // Create a pseudo SingleLine field
        $field = new SingleLineText();
        $field->handle = "formieCaptchaQuestion[$questionIndex]";
        $field->label = $question;
        $field->required = true;

        // Render the field using the standard renderer
        // return Formie::$plugin->getRendering()->renderField($form, $field);

        $variables = [];
        $variables['form'] = $form;
        $variables['field'] = $field;
        $variables['renderOptions'] = [];

        return $form->renderTemplate('integrations/captchas/question/field', $variables);
    }

    public function getRefreshJsVariables(Form $form, $page = null): array
    {
        return [];
    }
    
    public function getGqlVariables(Form $form, $page = null): array
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
        if (empty($this->questions)) {
            return true;
        }

        // Grab all answers as an array
        $answers = $this->getCaptchaValue($submission, 'fields[formieCaptchaQuestion]');

        // It should now be an array like [1 => 'answer']
        if (!is_array($answers) || empty($answers)) {
            $submission->addError('formieCaptchaQuestion', Craft::t('formie', 'Invalid question.'));
            return false;
        }

        // Get the first (and only) key and value
        $index = array_key_first($answers);
        $rawAnswer = $answers[$index] ?? null;

        if (!isset($this->questions[$index])) {
            $submission->addError('formieCaptchaQuestion', Craft::t('formie', 'Invalid question.'));
            return false;
        }

        $normalizedAnswer = $this->_normalizeAnswer((string)$rawAnswer);

        $validAnswers = array_map(fn($ans) => $this->_normalizeAnswer($ans), explode(',', $this->questions[$index]['answers'] ?? ''));

        if (!in_array($normalizedAnswer, $validAnswers, true)) {
            $submission->addError("formieCaptchaQuestion[$index]", Craft::t('formie', 'Incorrect answer. Please try again.'));
            return false;
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _normalizeAnswer(string $value): string
    {
        $value = trim(strtolower($value));
        $value = preg_replace('/[^\p{L}\p{N}]/u', '', $value); // remove punctuation/symbols

        return $value;
    }
}
