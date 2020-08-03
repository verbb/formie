<?php
namespace verbb\formie\integrations\captchas;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\base\Captcha;

use Craft;

class Honeypot extends Captcha
{
    // Constants
    // =========================================================================

    const HONEYPOT_INPUT_NAME = 'beesknees';


    // Properties
    // =========================================================================

    public $handle = 'honeypot';


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function getName(): string
    {
        return Craft::t('formie', 'Honeypot');
    }

    /**
     * @inheritDoc
     */
    public function getIconUrl(): string
    {
        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/captchas/dist/img/honeypot.svg', true);
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Check for bots that auto-fill forms, by providing an additional hidden field that should be left blank.');
    }

    /**
     * @inheritDoc
     */
    public function getFormSettingsHtml(Form $form): string
    {
        return Craft::$app->getView()->renderTemplate('formie/integrations/captchas/_form-settings', [
            'integration' => $this,
            'form' => $form,
        ]);
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
        if (Craft::$app->getRequest()->getParam(self::HONEYPOT_INPUT_NAME)) {
            return false;           
        }

        return true;
    }

}
