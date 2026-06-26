<?php
namespace verbb\formie\controllers\client;

use verbb\formie\Formie;

use Craft;

trait ClientGuestCsrfTrait
{
    // Protected Methods
    // =========================================================================

    protected function configureGuestCsrfValidation(array $actionIds): void
    {
        if (!in_array($this->action->id, $actionIds, true)) {
            return;
        }

        $settings = Formie::$plugin->getSettings();

        if (Craft::$app->getUser()->isGuest && !$settings->enableCsrfValidationForGuests) {
            $this->enableCsrfValidation = false;
        }
    }
}
