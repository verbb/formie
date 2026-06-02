<?php
namespace verbb\formie\deprecations;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\fields as formiefields;

use Craft;

trait SubmissionsDeprecations
{
    // Public Methods
    // =========================================================================

    public function processPayments(Submission $submission): bool
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Submissions `processPayments()` has been deprecated. Standalone payment processing is no longer part of the canonical workflow path, but this shim remains for Formie 3 compatibility.');

        foreach ($submission->getFields() as $field) {
            if (!$field instanceof formiefields\Payment) {
                continue;
            }

            // Match the Formie 3 behavior for callers still invoking this API directly.
            if ($field->isConditionallyHidden($submission) || $field->getIsDisabled()) {
                continue;
            }

            if ($paymentIntegration = $field->getPaymentIntegration()) {
                $paymentIntegration->setField($field);

                if (!$paymentIntegration->processPayment($submission)) {
                    $submission->isIncomplete = true;

                    Craft::$app->getElements()->saveElement($submission, false);

                    return false;
                }
            }
        }

        return true;
    }

}
