<?php
namespace verbb\formie\integrations\captchas;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\base\Captcha;

use Craft;
use craft\web\View;

class Javascript extends Captcha
{
    // Constants
    // =========================================================================

    const JAVASCRIPT_INPUT_NAME = '__JSCHK';


    // Properties
    // =========================================================================

    public $handle = 'javascript';


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return Craft::t('formie', 'Javascript');
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Check if the user has Javascript enabled, and flag as spam if they do not.');
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndHtml(Form $form, $page = null): string
    {
        $sessionId = $this->getSessionKey($form, $page);

        // Create the unique token 
        $uniqueId = uniqid(self::JAVASCRIPT_INPUT_NAME . '_', false);
        $value = uniqid();

        // Save the generated input value so we can validate it properly. Also make it per-form
        Craft::$app->getSession()->set($sessionId, $value);

        // Set a hidden field with no value and use javascript to set it.
        $output = '<input type="hidden" id="' . $uniqueId . '" name="' . $sessionId . '" />';
        $js = '(function(){ document.getElementById("' . $uniqueId . '").value = "' . $value . '"; })();';

        Craft::$app->getView()->registerJs($js, View::POS_END);

        return $output;
    }

    /**
     * @inheritDoc
     */
    public function validateSubmission(Submission $submission): bool
    {
        $sessionId = $this->getSessionKey($submission->form);

        // Grab the value generated in our session when we generated the captcha
        $value = Craft::$app->getSession()->get($sessionId);

        // Check the provided value
        $jsset = Craft::$app->getRequest()->getParam($sessionId);

        // Compare the two - in case someone is being sneaky and just providing _any_ value for the captcha
        if ($value !== $jsset) {   
            return false;            
        }

        // Remove the session info (keep it around if it fails)
        Craft::$app->getSession()->remove($sessionId);

        return true;
    }


    // Private Methods
    // =========================================================================

    private function getSessionKey($form, $page = null)
    {
        $currentPage = $form->getCurrentPage();

        if ($page) {
            $currentPage = $page;
        }

        $array = array_filter([
            self::JAVASCRIPT_INPUT_NAME . '_',
            $form->id,
            $currentPage->id ?? null,
        ]);

        return implode('', $array);
    }

}
