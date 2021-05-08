<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\SentNotification;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\mail\Mailer as CraftMailer;
use craft\mail\Message;
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

    /**
     * @inheritdoc
     */
    public function saveSentNotification($submission, $email)
    {
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
            $toEmail = ($result = array_keys($to)) ? $result[0] : '';
        }

        if ($replyTo = $email->getReplyTo()) {
            $replyToEmail = ($result = array_keys($replyTo)) ? $result[0] : '';
            $replyToName = ($result = array_values($replyTo)) ? $result[0] : '';
        }

        // Make sure to truncate values
        $subject = StringHelper::safeTruncate($email->getSubject(), 255);

        $sentNotification = new SentNotification();
        $sentNotification->title = $subject;
        $sentNotification->formId = $submission->formId;
        $sentNotification->submissionId = $submission->id;
        $sentNotification->subject = $subject;
        $sentNotification->to = $toEmail;
        $sentNotification->replyTo = $replyToEmail;
        $sentNotification->replyToName = $replyToName;
        $sentNotification->from = $fromEmail;
        $sentNotification->fromName = $fromName;

        if ($cc = $email->getCc()) {
            $sentNotification->cc = $cc;
        }

        if ($bcc = $email->getBcc()) {
            $sentNotification->bcc = $bcc;
        }

        $body = $email->getSwiftMessage()->getBody();
        $children = $email->getSwiftMessage()->getChildren();

        if ($body) {
            $sentNotification->htmlBody = $body;
            $sentNotification->body = $body;
        } else if ($children) {
            foreach ($children as $child) {
                if ($child->getContentType() == 'text/html') {
                    $sentNotification->htmlBody = $child->getBody();
                }

                if ($child->getContentType() == 'text/plain') {
                    $sentNotification->body = $child->getBody();
                }
            }
        }

        $sentNotification->info = $this->getDeliveryInfo($email);

        if (!Craft::$app->getElements()->saveElement($sentNotification)) {
            $error = Craft::t('formie', 'Unable to save sent notification - {errors}.', [
                'errors' => Json::encode($sentNotification->getErrors()),
            ]);

            Formie::error($error);
        }
    }

    /**
     * @inheritdoc
     */
    public function getDeliveryInfo($email)
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
        $transportType->setAttributes($emailSettings->transportSettings);
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
    public function pruneSentNotifications()
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
            ->dateUpdated('< ' . $date->format('c'))
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
