<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;
use verbb\formie\compatibility\delivery\LegacyDeliveryJobTrait;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationResult;

use Craft;
use craft\queue\BaseJob;

use RuntimeException;

class SendNotification extends BaseJob implements DeliveryJobInterface
{
    // Traits
    // =========================================================================

    use DebuggableJobTrait;
    use LegacyDeliveryJobTrait;


    // Properties
    // =========================================================================

    public string $deliveryAttemptUid;


    // Public Methods
    // =========================================================================

    public function getDeliveryAttemptUid(): string
    {
        return $this->resolveDeliveryAttemptUid();
    }

    public function execute($queue): void
    {
        $uid = $this->getDeliveryAttemptUid();
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $row = $attempts->get($uid);
        $data = $attempts->data($uid);
        $submission = Submission::find()->id($row['submissionId'])->status(null)->isIncomplete(null)->isSpam(null)->one();
        $notification = Formie::$plugin->getNotifications()->getNotificationById($data['notificationId']);

        if (!$submission || !$notification || (int)$notification->formId !== (int)$submission->formId) {
            $attempts->execute($uid, fn() => IntegrationResult::rejected('notification_owner_unavailable'));
            throw new RuntimeException('Notification delivery owner is unavailable.');
        }

        // Apply the same site overrides used at enqueue time to the fresh record,
        // without reusing a notification cached by an earlier queue job.
        $notification = Formie::$plugin->getFormSiteOverrides()->applyToNotification($notification, (int)$submission->siteId);
        $sites = Craft::$app->getSites();
        $previousSite = $sites->getCurrentSite();
        $previousLanguage = Craft::$app->language;
        $previousLocale = Craft::$app->getLocale();

        try {
            Craft::$app->language = $submission->getSite()->language;
            Craft::$app->set('locale', Craft::$app->getI18n()->getLocaleById($submission->getSite()->language));
            Craft::$app->getSites()->setCurrentSite($submission->getSite());
            $response = Formie::$plugin->getNotifications()->sendNotificationEmail($notification, $submission, new self(['deliveryAttemptUid' => $uid]), $row['executionUid']);

            if ($response !== true && !($response['success'] ?? false)) {
                $message = 'Notification delivery ' . ($response['status'] ?? 'failed') . '.';

                if (!empty($response['message'])) {
                    $message .= ' ' . $response['message'];
                }
                throw new RuntimeException($message . ' Open this job in Utilities → Queue Manager and select Diagnostics for more information. Delivery attempt: ' . $uid . '.');
            }
            $this->setProgress($queue, 1);
        } finally {
            $sites->setCurrentSite($previousSite);
            Craft::$app->language = $previousLanguage;
            Craft::$app->set('locale', $previousLocale);
        }
    }


    // Protected Methods
    // =========================================================================

    protected function defaultDescription(): string
    {
        return Craft::t('formie', 'Sending form notification.');
    }
}
