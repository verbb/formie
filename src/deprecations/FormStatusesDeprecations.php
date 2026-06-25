<?php
namespace verbb\formie\deprecations;

use Craft;
use craft\events\ConfigEvent;

trait FormStatusesDeprecations
{
    // Constants
    // =========================================================================

    // Deprecated in 4.0.0
    public const CONFIG_FORM_STATUSES_KEY = 'formie.formStatuses';


    // Public Methods
    // =========================================================================

    public function handleChangedStatus(ConfigEvent $event): void
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Formie `handleChangedStatus()` has been deprecated. Use `handleChangedFormStatus()` instead.');

        $this->handleChangedFormStatus($event);
    }

    public function handleDeletedStatus(ConfigEvent $event): void
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Formie `handleDeletedStatus()` has been deprecated. Use `handleDeletedFormStatus()` instead.');

        $this->handleDeletedFormStatus($event);
    }
}
