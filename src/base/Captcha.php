<?php
namespace verbb\formie\base;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

use Craft;
use craft\helpers\StringHelper;

abstract class Captcha extends Integration
{
    // Static Methods
    // =========================================================================

    public static function typeName(): string
    {
        return Craft::t('formie', 'Captchas');
    }

    public static function supportsConnection(): bool
    {
        return false;
    }

    public static function supportsPayloadSending(): bool
    {
        return false;
    }

    // Properties
    // =========================================================================

    public bool $showAllPages = false;
    public ?string $spamReason = null;


    // Public Methods
    // =========================================================================

    public function getIconUrl(): string
    {
        $handle = StringHelper::toKebabCase($this->getHandle());

        return Craft::$app->getAssetManager()->getPublishedUrl("@verbb/formie/web/assets/captchas/dist/img/{$handle}.svg", true);
    }

    public function getFormSettingsHtml($form): string
    {
        return Craft::$app->getView()->renderTemplate('formie/integrations/captchas/_form-settings', [
            'integration' => $this,
            'form' => $form,
        ]);
    }

    /**
     * Returns the frontend HTML.
     *
     * @param Form $form
     * @param null $page
     * @return string
     */
    public function getFrontEndHtml(Form $form, $page = null): string
    {
        return '';
    }

    /**
     * Returns the front-end JS.
     *
     * @param Form $form
     * @param null $page
     * @return array|null
     */
    public function getFrontEndJsVariables(Form $form, $page = null): ?array
    {
        return null;
    }

    /**
     * Some captchas require tokens to be refreshed for static caching. You should return any
     * variables used in `getFrontEndJsVariables()` here for the refresh-token action to return.
     *
     * @param Form $form
     * @param null $page
     * @return array|null
     */
    public function getRefreshJsVariables(Form $form, $page = null): ?array
    {
        return null;
    }

    /**
     * Validates the submission.
     *
     * @param Submission $submission
     * @return bool
     */
    public function validateSubmission(Submission $submission): bool
    {
        return true;
    }


    // Protected Methods
    // =========================================================================

    protected function getOrSet($key, $callable)
    {
        if ($value = Craft::$app->getSession()->get($key)) {
            return $value;
        }

        $value = $callable($this);

        Craft::$app->getSession()->set($key, $value);

        return $value;
    }
}
