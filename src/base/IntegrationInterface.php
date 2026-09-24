<?php
namespace verbb\formie\base;

use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;

use craft\base\SavableComponentInterface;

interface IntegrationInterface extends SavableComponentInterface
{
    public function getFormSettingAttributes(): array;
    public function getFormSettingsSchema(FormInterface $form): array;
    public function getClientModule(ClientModuleContext $context): ?ClientModule;

    /**
     * Returns the CP icon URL for use in builder summaries/lists.
     * Implementations may internally cache published dist URLs.
     */
    public function getCpIconUrl(?string $distBaseUrl = null): string;
}
