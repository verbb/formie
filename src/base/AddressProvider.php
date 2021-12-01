<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

use Craft;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;

abstract class AddressProvider extends Integration implements IntegrationInterface
{
    // Properties
    // =========================================================================


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function typeName(): string
    {
        return Craft::t('formie', 'Address Providers');
    }

    /**
     * @inheritDoc
     */
    public static function supportsConnection(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function supportsPayloadSending(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function supportsCurrentLocation(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function hasFormSettings(): bool
    {
        return false;
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getIconUrl(): string
    {
        $handle = StringHelper::toKebabCase($this->displayName());

        return Craft::$app->getAssetManager()->getPublishedUrl("@verbb/formie/web/assets/addressproviders/dist/img/{$handle}.svg", true);
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): string
    {
        $handle = StringHelper::toKebabCase($this->displayName());

        return Craft::$app->getView()->renderTemplate("formie/integrations/address-providers/{$handle}/_settings", [
            'integration' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('formie/settings/address-providers/edit/' . $this->id);
    }

    /**
     * Returns the frontend HTML.
     *
     * @param Form $form
     * @return string
     */
    public function getFrontEndHtml($field, $options): string
    {
        return '';
    }

    /**
     * Returns the front-end JS.
     *
     * @return string
     */
    public function getFrontEndJsVariables($field = null)
    {
        return null;
    }
}
