<?php
namespace verbb\formie\base;

use craft\base\Model;
use craft\helpers\UrlHelper;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

abstract class Integration extends Model implements IntegrationInterface
{
    // Properties
    // =========================================================================

    public $enabled;
    public $type;
    public $settings;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return static::getName();
    }

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
}
