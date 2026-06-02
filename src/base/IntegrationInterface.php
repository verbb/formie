<?php
namespace verbb\formie\base;

use craft\base\SavableComponentInterface;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;

interface IntegrationInterface extends SavableComponentInterface
{
    public function getFormSettingsSchema(FormInterface $form): array;
    public function getClientModule(ClientModuleContext $context): ?ClientModule;

    /**
     * Returns the CP icon URL for use in builder summaries/lists.
     * Implementations may internally cache published dist URLs.
     */
    public function getCpIconUrl(?string $distBaseUrl = null): string;
}