<?php
namespace verbb\formie\base;

use craft\base\ComponentInterface;
use verbb\formie\elements\Form;

interface IntegrationInterface extends ComponentInterface
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the name of the integration.
     *
     * @return string
     */
    public static function getName(): string;

    /**
     * @return bool
     */
    public static function isSelectable(): bool;

    /**
     * Returns the icon path for the integration.
     *
     * @return string
     */
    public function getIconUrl(): string;

    /**
     * Returns the HTML description for the integration.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Returns whether the integration's settings are valid.
     *
     * @return bool
     */
    public function hasValidSettings(): bool;

    /**
     * @param Form $form
     * @return string
     */
    public function getFormSettingsHtml(Form $form): string;
}
