<?php
namespace verbb\formie\deprecations;

use verbb\formie\elements\Form;

use Craft;

trait FormGroupPolicyDeprecations
{
    // Public Methods
    // =========================================================================

    public function getStatusesForForm(?Form $form): array
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Formie `getStatusesForForm()` has been deprecated. Use `getSubmissionStatusesForForm()` instead.');

        return $this->getSubmissionStatusesForForm($form);
    }

    public function getStatusSelectOptions(?Form $form): array
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Formie `getStatusSelectOptions()` has been deprecated. Use `getSubmissionStatusSelectOptions()` instead.');

        return $this->getSubmissionStatusSelectOptions($form);
    }

    public function describeAllowedStatusSource(?Form $form): ?string
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Formie `describeAllowedStatusSource()` has been deprecated. Use `describeAllowedSubmissionStatusSource()` instead.');

        return $this->describeAllowedSubmissionStatusSource($form);
    }
}
