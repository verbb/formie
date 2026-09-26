<?php
namespace verbb\formie\base;

use verbb\formie\models\BrowserModuleEntry;
use verbb\formie\models\BrowserModuleContext;

use craft\base\SavableComponentInterface;

interface IntegrationInterface extends SavableComponentInterface
{
    public function getFormSettingAttributes(): array;
    public function getFormSettingsSchema(FormInterface $form): array;
    public function getBrowserModule(BrowserModuleContext $context): ?BrowserModuleEntry;

    /**
     * Returns the CP icon URL for use in builder summaries/lists.
     * Implementations may internally cache published dist URLs.
     */
    public function getCpIconUrl(?string $distBaseUrl = null): string;
}
