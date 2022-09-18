<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\elements\SentNotification;
use verbb\formie\models\Notification;
use verbb\formie\models\Settings;

use Craft;
use craft\helpers\App;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\mail\transportadapters\BaseTransportAdapter;
use craft\mail\transportadapters\Smtp;

use yii\base\Component;

use DateInterval;
use DateTime;
use Throwable;

class SentNotifications extends Component
{
    // Public Methods
    // =========================================================================

    public function saveSentNotification(Submission $submission, Notification $notification, mixed $email, mixed $success = true, mixed $error = null): void
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        if (!$settings->sentNotifications) {
            return;
        }

        $fromEmail = '';
        $fromName = '';
        $replyToEmail = '';
        $replyToName = '';
        $toEmail = '';

        if ($from = $email->getFrom()) {
            $fromEmail = ($result = array_keys($from)) ? $result[0] : '';
            $fromName = ($result = array_values($from)) ? $result[0] : '';
        }

        if ($to = $email->getTo()) {
            $toEmail = implode(',', array_keys($to));
        }

        if ($replyTo = $email->getReplyTo()) {
            $replyToEmail = ($result = array_keys($replyTo)) ? $result[0] : '';
            $replyToName = ($result = array_values($replyTo)) ? $result[0] : '';
        }

        // Make sure to truncate values
        $subject = StringHelper::safeTruncate((string)$email->getSubject(), 255);
        $replyToName = StringHelper::safeTruncate((string)$replyToName, 255);
        $fromName = StringHelper::safeTruncate((string)$fromName, 255);

        $sentNotification = new SentNotification();
        $sentNotification->title = $notification->name;
        $sentNotification->formId = $submission->formId;
        $sentNotification->submissionId = $submission->id;
        $sentNotification->notificationId = $notification->id;
        $sentNotification->subject = $subject;
        $sentNotification->to = $toEmail;
        $sentNotification->replyTo = $replyToEmail;
        $sentNotification->replyToName = $replyToName;
        $sentNotification->from = $fromEmail;
        $sentNotification->fromName = $fromName;

        // Store state and any errors
        $sentNotification->success = $success;
        $sentNotification->message = $error;

        if ($cc = $email->getCc()) {
            $sentNotification->cc = implode(',', array_keys($cc));
        }

        if ($bcc = $email->getBcc()) {
            $sentNotification->bcc = implode(',', array_keys($bcc));
        }

        if ($sender = $email->getSender()) {
            $sentNotification->sender = implode(',', array_keys($sender));
        }

        $sentNotification->htmlBody = $email->getHtmlBody();
        $sentNotification->body = $email->getTextBody();

        $sentNotification->info = $this->getDeliveryInfo($email);

        if (!Craft::$app->getElements()->saveElement($sentNotification)) {
            $error = Craft::t('formie', 'Unable to save sent notification - {errors}.', [
                'errors' => Json::encode($sentNotification->getErrors()),
            ]);

            Formie::error($error);
        }
    }

    public function getDeliveryInfo($email): array
    {
        $info = [];

        $request = Craft::$app->getRequest();

        $info['formieVersion'] = 'Formie ' . Formie::$plugin->getVersion();
        $info['craftVersion'] = 'Craft ' . Craft::$app->getEditionName() . ' ' . Craft::$app->getVersion();

        if ($request->getIsConsoleRequest()) {
            $info['ipAddress'] = 'Console Request';
            $info['userAgent'] = 'Console Request';
        } else {
            $info['ipAddress'] = $request->getUserIP();
            $info['userAgent'] = $request->getUserAgent();
        }

        $emailSettings = App::mailSettings();

        /** @var BaseTransportAdapter $transportType */
        $transportType = new $emailSettings->transportType();

        if ($emailSettings->transportSettings) {
            $transportType->setAttributes($emailSettings->transportSettings, false);
        }

        $info['transportType'] = $transportType::displayName();

        if (get_class($transportType) === Smtp::class) {
            /** @var Smtp $transportType */
            $info['host'] = $transportType->host;
            $info['port'] = $transportType->port;
            $info['username'] = $transportType->username;
            $info['encryptionMethod'] = $transportType->encryptionMethod;
            $info['timeout'] = $transportType->timeout;
        }

        return $info;
    }

    /**
     * Deletes sent notifications older than the configured interval.
     */
    public function pruneSentNotifications(): void
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        if ($settings->maxSentNotificationsAge <= 0) {
            return;
        }

        $interval = new DateInterval("P{$settings->maxSentNotificationsAge}D");
        $date = new DateTime();
        $date->sub($interval);

        $sentNotifications = SentNotification::find()
            ->dateUpdated('< ' . Db::prepareDateForDb($date))
            ->all();

        foreach ($sentNotifications as $sentNotification) {
            try {
                Craft::$app->getElements()->deleteElement($sentNotification, true);
            } catch (Throwable $e) {
                Formie::error('Failed to prune sent notification with ID: #' . $sentNotification->id);
            }
        }
    }
}
