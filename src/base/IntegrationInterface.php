<?php
namespace verbb\formie\base;

use verbb\formie\models\BrowserModule;
use verbb\formie\models\BrowserModuleContext;
use verbb\formie\models\IntegrationConfig;

use craft\base\SavableComponentInterface;

interface IntegrationInterface extends SavableComponentInterface
{
    public function getFormSettingAttributes(): array;
    public function getFormSettingsSchema(FormInterface $form): array;
    public function getConfig(bool $useCache = true): IntegrationConfig;
    public function refreshConfig(): IntegrationConfig;
    public function supportsConfigRefresh(): bool;
    public function getBrowserModule(BrowserModuleContext $context): ?BrowserModule;

    /**
     * Returns the CP icon URL for use in builder summaries/lists.
     * Implementations may internally cache published dist URLs.
     */
    public function getCpIconUrl(?string $distBaseUrl = null): string;
}
