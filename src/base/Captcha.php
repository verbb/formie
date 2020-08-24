<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

use Craft;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;

abstract class Captcha extends Integration implements IntegrationInterface
{
    // Properties
    // =========================================================================

    public $showAllPages = false;


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function typeName(): string
    {
        return Craft::t('formie', 'Captchas');
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getIconUrl(): string
    {
        $handle = StringHelper::toKebabCase($this->getHandle());

        return Craft::$app->getAssetManager()->getPublishedUrl("@verbb/formie/web/assets/captchas/dist/img/{$handle}.svg", true);
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
     * Returns the frontend HTML.
     *
     * @param Form $form
     * @return string
     */
    public function getFrontEndHtml(Form $form, $page = null): string
    {
        return '';
    }

    /**
     * Returns the front-end JS.
     *
     * @return string
     */
    public function getFrontEndJsVariables(Form $form, $page = null)
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
}
