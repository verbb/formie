<?php
namespace verbb\formie\integrations\captchas;

use verbb\formie\base\Captcha;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\FieldLayoutPage;

use Craft;

class Honeypot extends Captcha
{
    // Constants
    // =========================================================================

    public const HONEYPOT_INPUT_NAME = 'beesknees';


    // Properties
    // =========================================================================

    public ?string $handle = 'honeypot';


    // Public Methods
    // =========================================================================

    public function getName(): string
    {
        return Craft::t('formie', 'Honeypot');
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Check for bots that auto-fill forms, by providing an additional hidden field that should be left blank.');
    }

    public function getFrontEndHtml(Form $form, FieldLayoutPage $page = null): string
    {
        $sessionKey = $this->getSessionKey($form, $page);

        $label = Craft::t('formie', 'Leave this field blank');

        $output = '<div id="' . $sessionKey . '_wrapper" style="display:none;">';
        $output .= '<label for="' . $sessionKey . '">' . $label . '</label>';
        $output .= '<input type="text" id="' . $sessionKey . '" name="' . self::HONEYPOT_INPUT_NAME . '" style="display:none;" />';
        $output .= '</div>';

        return $output;
    }

    public function getRefreshJsVariables(Form $form, FieldLayoutPage $page = null): array
    {
        return [
            'formId' => $form->getFormId(),
            'sessionKey' => self::HONEYPOT_INPUT_NAME,
        ];
    }
    
    public function getGqlVariables(Form $form, FieldLayoutPage $page = null): array
    {
        return $this->getRefreshJsVariables($form, $page);
    }

    public function validateSubmission(Submission $submission): bool
    {
        $value = $this->getCaptchaValue($submission, self::HONEYPOT_INPUT_NAME);

        // The honeypot field must be left blank
        if ($value) {
            $this->spamReason = Craft::t('formie', 'Honeypot input has value: {v}.', ['v' => $value]);

            return false;
        }

        // If the honeypot param has been stripped out of the request altogether
        if ($value === null) {
            $this->spamReason = Craft::t('formie', 'Honeypot param missing: {v}.', ['v' => self::HONEYPOT_INPUT_NAME]);

            return false;
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    private function getSessionKey(Form $form, FieldLayoutPage $page = null): string
    {
        // Default the page to the last page, if not set.
        if (!$page) {
            $pages = $form->getPages();
            $page = $pages[count($pages) - 1] ?? $page;
        }

        $array = array_filter([
            self::HONEYPOT_INPUT_NAME . '_',
            $form->id,
            $page->id ?? null,
        ]);

        return implode('', $array);
    }

}
