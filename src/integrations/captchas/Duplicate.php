<?php
namespace verbb\formie\integrations\captchas;

use verbb\formie\base\Captcha;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

use Craft;

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

    /**
     * @inheritDoc
     */
    public function getFrontEndHtml(Form $form, $page = null): string
    {
        $sessionKey = $this->getSessionKey($form, $page);

        // Get or create the generated input value, so we can validate it properly. Also make it per-form
        $value = $this->getOrSet($sessionKey, function() {
            return uniqid('', true);
        });

        // Set a hidden field with no value and use javascript to set it.
        return '<input type="hidden" name="' . $sessionKey . '" value="' . $value . '" />';
    }

    /**
     * @inheritDoc
     */
    public function getRefreshJsVariables(Form $form, $page = null): array
    {
        $sessionKey = $this->getSessionKey($form, $page);

        // Get or create the generated input value, so we can validate it properly. Also make it per-form
        $value = $this->getOrSet($sessionKey, function() {
            return uniqid('', true);
        });

        return [
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
        $jsset = $this->getRequestParam($sessionKey);

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

    private function getSessionKey($form, $page = null): string
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
