<?php
namespace verbb\formie\integrations\captchas;

use verbb\formie\base\Captcha;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\FieldLayoutPage;

use Craft;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\web\View;

class Duplicate extends Captcha
{
    // Constants
    // =========================================================================

    public const DUPLICATE_INPUT_NAME = '__DUP';


    // Properties
    // =========================================================================

    public ?string $handle = 'duplicate';


    // Public Methods
    // =========================================================================

    public function getName(): string
    {
        return Craft::t('formie', 'Duplicate');
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Check for duplicate submissions, where bots might be submitting multiple times.');
    }

    public function getFrontEndHtml(Form $form, FieldLayoutPage $page = null): string
    {
        return Html::tag('div', null, [
            'class' => 'formie-duplicate-captcha-placeholder',
            'data-duplicate-captcha-placeholder' => true,
        ]);
    }

    public function getFrontEndJsVariables(Form $form, $page = null): ?array
    {
        $sessionKey = $this->getSessionKey($form, $page);

        // Get or create the generated input value, so we can validate it properly. Also make it per-form
        $value = $this->getOrSet($sessionKey, function() {
            return uniqid('', true);
        });

        $settings = [
            'formId' => $form->getFormId(),
            'sessionKey' => $sessionKey,
            'value' => $value,
        ];

        $src = Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/captchas/duplicate.js');

        return [
            'src' => $src,
            'module' => 'FormieDuplicateCaptcha',
            'settings' => $settings,
        ];
    }

    public function getRefreshJsVariables(Form $form, FieldLayoutPage $page = null): array
    {
        $sessionKey = $this->getSessionKey($form, $page);

        // Get or create the generated input value, so we can validate it properly. Also make it per-form
        $value = $this->getOrSet($sessionKey, function() {
            return uniqid('', true);
        });

        return [
            'formId' => $form->getFormId(),
            'sessionKey' => $sessionKey,
            'value' => $value,
        ];
    }
    
    public function getGqlVariables(Form $form, FieldLayoutPage $page = null): array
    {
        return $this->getRefreshJsVariables($form, $page);
    }

    public function validateSubmission(Submission $submission): bool
    {
        $sessionKey = $this->getSessionKey($submission->form);

        // Grab the value generated in our session when we generated the captcha
        $value = Craft::$app->getSession()->get($sessionKey);

        // Check the provided value
        $jsset = $this->getCaptchaValue($submission, $sessionKey);

        // Protect against invalid data being sent. No need to log, likely malicious
        if (!is_string($jsset)) {
            return false;
        }

        // Compare the two - in case someone is being sneaky and just providing _any_ value for the captcha
        if ($value !== $jsset) {
            $this->spamReason = Craft::t('formie', 'Value mismatch {a}:{b}.', ['a' => $value, 'b' => $jsset]);

            return false;
        }

        // Remove the session info (keep it around if it fails)
        Craft::$app->getSession()->remove($sessionKey);

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
            self::DUPLICATE_INPUT_NAME . '_',
            $form->id,
            $page->id ?? null,
        ]);

        return implode('', $array);
    }

}
