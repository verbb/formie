<?php
namespace verbb\formie\integrations\captchas;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\base\Captcha;

use Craft;
use craft\helpers\Json;
use craft\web\View;

class Javascript extends Captcha
{
    // Constants
    // =========================================================================

    const JAVASCRIPT_INPUT_NAME = '__JSCHK';


    // Properties
    // =========================================================================

    public $handle = 'javascript';
    public $minTime;


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
    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/integrations/captchas/javascript/_plugin-settings', [
            'integration' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndHtml(Form $form, $page = null): string
    {
        $sessionKey = $this->getSessionKey($form, $page);

        // Set the init time, if we need it
        if ($this->minTime) {
            Craft::$app->getSession()->set($sessionKey . '_init', time());
        }

        return '<div class="formie-jscaptcha-placeholder"></div>';
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndJsVariables(Form $form, $page = null)
    {
        $sessionKey = $this->getSessionKey($form, $page);

        // Get or create the generated input value so we can validate it properly. Also make it per-form
        $value = $this->getOrSet($sessionKey, function() {
            return uniqid();
        });

        $settings = [
            'formId' => $form->getFormId(),
            'sessionKey' => $sessionKey,
            'value' => $value,
        ];

        $src = Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/captchas/dist/js/javascript.js', true);

        // Add the JS value separately, so it's not cached in the form as settings
        $js = 'window.Formie' . $sessionKey . '=' . Json::encode($value) . ';';
        Craft::$app->getView()->registerJs($js, View::POS_END);

        return [
            'src' => $src,
            'module' => 'FormieJSCaptcha',
            'settings' => $settings,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getRefreshJsVariables(Form $form, $page = null)
    {
        $sessionKey = $this->getSessionKey($form, $page);
        
        // Get or create the generated input value so we can validate it properly. Also make it per-form
        $value = $this->getOrSet($sessionKey, function() {
            return uniqid();
        });

        return [
            'formId' => $form->getFormId(),
            'sessionKey' => $sessionKey,
            'value' => $value,
        ];
    }

    /**
     * @inheritDoc
     */
    public function validateSubmission(Submission $submission): bool
    {
        $sessionKey = $this->getSessionKey($submission->form);

        // Grab the value generated in our session when we generated the captcha
        $value = Craft::$app->getSession()->get($sessionKey);

        // Check the provided value
        $jsset = Craft::$app->getRequest()->getParam($sessionKey);

        // Compare the two - in case someone is being sneaky and just providing _any_ value for the captcha
        if ($value !== $jsset) {
            $this->spamReason = Craft::t('formie', 'Value mismatch {a}:{b}.', ['a' => $value, 'b' => $jsset]);

            return false;
        }

        // If we're checking against a min time?
        if ($this->minTime) {
            $initTime = time() - Craft::$app->getSession()->get($sessionKey . '_init');

            // Remove the session
            Craft::$app->getSession()->remove($sessionKey . '_init');

            if ($initTime <= $this->minTime) {
                $this->spamReason = Craft::t('formie', 'Submitted in {s}s, below the {m}s setting.', ['s' => $initTime, 'm' => $this->minTime]);

                return false;
            }
        }

        // Remove the session info (keep it around if it fails)
        Craft::$app->getSession()->remove($sessionKey);

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
