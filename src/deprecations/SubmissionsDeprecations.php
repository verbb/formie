<?php
namespace verbb\formie\deprecations;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\fields as formiefields;
use verbb\formie\models\IntegrationResponse;
use verbb\formie\models\IntegrationTriggerRequest;
use verbb\formie\models\Notification;
use verbb\formie\services\IntegrationTriggers;
use verbb\formie\services\SubmissionWorkflow;

use Craft;

trait SubmissionsDeprecations
{
    // Public Methods
    // =========================================================================

    public function processPayments(Submission $submission): bool
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Submissions `processPayments()` has been deprecated. Let Formie process submissions through the submission workflow instead.');

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

    public function sendNotifications(Submission $submission): void
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Submissions `sendNotifications()` has been deprecated. Use `Formie::$plugin->getNotifications()->sendNotifications()` instead.');

        Formie::$plugin->getNotifications()->sendNotifications($submission);
    }

    public function sendNotification(Notification $notification, Submission $submission, ?bool $useQueue = null): void
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Submissions `sendNotification()` has been deprecated. Use `Formie::$plugin->getNotifications()->sendNotification()` instead.');

        Formie::$plugin->getNotifications()->sendNotification($notification, $submission, $useQueue);
    }

    public function sendNotificationEmail(Notification $notification, Submission $submission, $queueJob = null): array|bool
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Submissions `sendNotificationEmail()` has been deprecated. Use `Formie::$plugin->getNotifications()->sendNotificationEmail()` instead.');

        return Formie::$plugin->getNotifications()->sendNotificationEmail($notification, $submission, $queueJob);
    }

    public function triggerIntegrations(
        Submission $submission,
        string $processMode = SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        ?string $triggerEvent = null,
        bool $operatorInitiated = false,
    ): void {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Submissions `triggerIntegrations()` has been deprecated. Use `Formie::$plugin->getIntegrationTriggers()->dispatch()` instead.');

        Formie::$plugin->getIntegrationTriggers()->dispatch(new IntegrationTriggerRequest([
            'source' => IntegrationTriggers::SOURCE_WORKFLOW,
            'submission' => $submission,
            'processMode' => $processMode,
            'triggerEvent' => $triggerEvent,
            'operatorInitiated' => $operatorInitiated,
        ]));
    }

    public function sendIntegrationPayload(Integration $integration, Submission $submission): bool|IntegrationResponse
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Submissions `sendIntegrationPayload()` has been deprecated. Use `Formie::$plugin->getIntegrations()->sendIntegrationPayload()` for workflow dispatch, or `Formie::$plugin->getIntegrationTriggers()->dispatchManualIntegration()` for operator-initiated runs.');

        return Formie::$plugin->getIntegrations()->sendIntegrationPayload($integration, $submission);
    }
}
