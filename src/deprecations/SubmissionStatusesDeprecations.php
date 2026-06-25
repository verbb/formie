<?php
namespace verbb\formie\deprecations;

use verbb\formie\elements\Form;

use Craft;
use craft\events\ConfigEvent;

trait SubmissionStatusesDeprecations
{
    // Constants
    // =========================================================================
   
    // Deprecated in 4.0.0
    public const CONFIG_STATUSES_KEY = 'formie.statuses';


    // Public Methods
    // =========================================================================

    public function getStatusesForForm(?Form $form): array
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Formie `getStatusesForForm()` has been deprecated. Use `getSubmissionStatusesForForm()` instead.');

        return $this->getSubmissionStatusesForForm($form);
    }

    public function handleChangedStatus(ConfigEvent $event): void
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Formie `handleChangedStatus()` has been deprecated. Use `handleChangedSubmissionStatus()` instead.');

        $this->handleChangedSubmissionStatus($event);
    }

    public function handleDeletedStatus(ConfigEvent $event): void
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Formie `handleDeletedStatus()` has been deprecated. Use `handleDeletedSubmissionStatus()` instead.');

        $this->handleDeletedSubmissionStatus($event);
    }
}
