<?php
namespace verbb\formie\integrations\captchas;

use verbb\formie\base\Captcha;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

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

    /**
     * @inheritDoc
     */
    public function getFrontEndHtml(Form $form, $page = null): string
    {
        $uniqueId = uniqid(self::HONEYPOT_INPUT_NAME, false);
        $label = Craft::t('formie', 'Leave this field blank');

        $output = '<div id="' . $uniqueId . '_wrapper" style="display:none;">';
        $output .= '<label for="' . $uniqueId . '">' . $label . '</label>';
        $output .= '<input type="text" id="' . $uniqueId . '" name="' . self::HONEYPOT_INPUT_NAME . '" style="display:none;" />';
        $output .= '</div>';

        return $output;
    }

    /**
     * @inheritDoc
     */
    public function validateSubmission(Submission $submission): bool
    {
        // The honeypot field must be left blank
        if ($this->getRequestParam(self::HONEYPOT_INPUT_NAME)) {
            return false;
        }

        return true;
    }

}
