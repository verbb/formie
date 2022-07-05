<?php
namespace verbb\formie\base;

use Craft;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;

abstract class AddressProvider extends Integration
{
    // Static Methods
    // =========================================================================

    public static function typeName(): string
    {
        return Craft::t('formie', 'Address Providers');
    }

    public static function supportsConnection(): bool
    {
        return false;
    }

    public static function supportsPayloadSending(): bool
    {
        return false;
    }

    public static function supportsCurrentLocation(): bool
    {
        return false;
    }

    public static function hasFormSettings(): bool
    {
        return false;
    }


    // Public Methods
    // =========================================================================

    public function getIconUrl(): string
    {
        $handle = StringHelper::toKebabCase(static::displayName());

        return Craft::$app->getAssetManager()->getPublishedUrl("@verbb/formie/web/assets/cp/dist/img/addressproviders/{$handle}.svg", true);
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): ?string
    {
        $handle = StringHelper::toKebabCase(static::displayName());

        return Craft::$app->getView()->renderTemplate("formie/integrations/address-providers/{$handle}/_settings", [
            'integration' => $this,
        ]);
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('formie/settings/address-providers/edit/' . $this->id);
    }

    /**
     * Returns the frontend HTML.
     *
     * @param $field
     * @param $renderOptions
     * @return string
     */
    public function getFrontEndHtml($field, array $renderOptions = []): string
    {
        return '';
    }

    /**
     * Returns the front-end JS variables.
     */
    public function getFrontEndJsVariables($field = null): ?array
    {
        return null;
    }
}
