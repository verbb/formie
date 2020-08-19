<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;

use craft\base\Model;
use craft\helpers\UrlHelper;

abstract class Integration extends Model implements IntegrationInterface
{
    // Properties
    // =========================================================================

    public $enabled;
    public $type;
    public $settings;


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return static::getName();
    }

    /**
     * Whether this integration supports connections (checking to see if the API is connected).
     *
     * @return bool
     */
    public static function supportsConnection(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function supportsOauthConnection(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function log($integration, $message, $throwError = false)
    {
        Formie::log($integration->name . ': ' . $message);

        if ($throwError) {
            throw new IntegrationException($message);
        }
    }

    /**
     * @inheritDoc
     */
    public static function error($integration, $message, $throwError = false)
    {
        Formie::error($integration->name . ': ' . $message);

        if ($throwError) {
            throw new IntegrationException($message);
        }
    }


    // Public Methods
    // =========================================================================

    /**
     * Returns the control panel edit url for the integration.
     *
     * @return string
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('formie/settings/integrations/' . $this->handle);
    }

    /**
     * Returns the settings HTML.
     *
     * @return string
     */
    public function getSettingsHtml(): string
    {
        return '';
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

    /**
     * Whether this integration has settings editable for the whole form.
     *
     * @return bool
     */
    public function hasFormSettings(): bool
    {
        return true;
    }

    /**
     * Returns the front-end JS.
     *
     * @return string
     */
    public function getFrontEndJs(Form $form, $page = null) {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function beforeSave(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function afterSave()
    {

    }
}
