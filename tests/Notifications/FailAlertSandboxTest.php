<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\integrations\miscellaneous\Trello;
use verbb\formie\models\Notification;

it('sends failure alerts with the Twig sandbox enabled', function (): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $submission = formie()->submission($form)->with(['fullName' => 'Alert'])->save();
    $notification = new Notification(['formId' => $form->id, 'name' => 'Admin email', 'handle' => 'adminEmail']);
    $integration = new Trello(['name' => 'Order board', 'handle' => 'orderBoard']);

    $general = Craft::$app->getConfig()->getGeneral();
    $settings = Formie::$plugin->getSettings();
    $original = [$general->enableTwigSandbox, $settings->sendEmailAlerts, $settings->alertEmails, $settings->sendIntegrationAlerts, $settings->integrationAlertEmails];
    $general->enableTwigSandbox = true;
    $settings->sendEmailAlerts = true;
    $settings->alertEmails = [['Admin', 'alerts@example.test']];
    $settings->sendIntegrationAlerts = true;
    $settings->integrationAlertEmails = [['Admin', 'alerts@example.test']];

    $sent = [];
    $capture = static function ($event) use (&$sent) {
        $sent[] = $event->message->getSubject() . "\n" . $event->message->getSymfonyEmail()->getTextBody();
        $event->isValid = false;
    };
    $mailer = Craft::$app->getMailer();
    $mailer->on(\yii\mail\BaseMailer::EVENT_BEFORE_SEND, $capture);

    try {
        Formie::$plugin->getEmails()->sendFailAlertEmail($notification, $submission, ['error' => 'Mailbox full'], 'sandbox-alert');
        Formie::$plugin->getIntegrations()->sendFailAlertEmail($integration, $submission, new RuntimeException('Bad gateway'), ['status' => 502]);
    } finally {
        $mailer->off(\yii\mail\BaseMailer::EVENT_BEFORE_SEND, $capture);
        [$general->enableTwigSandbox, $settings->sendEmailAlerts, $settings->alertEmails, $settings->sendIntegrationAlerts, $settings->integrationAlertEmails] = $original;
    }

    expect($sent)->toHaveCount(2);
    expect($sent[0])->toContain('{"error":"Mailbox full"}');
    expect($sent[1])->toContain('Order board', 'Bad gateway', '{"status":502}');
});

it('says why a notification failed in its failure alert', function (): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $submission = formie()->submission($form)->with(['fullName' => 'Alert'])->save();
    $notification = new Notification(['formId' => $form->id, 'name' => 'Admin email', 'handle' => 'adminEmail', 'to' => 'staff@example.test', 'from' => 'sender@example.test', 'subject' => 'Enquiry', 'content' => 'Hello', 'enabled' => true]);
    expect(Formie::$plugin->getNotifications()->saveNotification($notification))->toBeTrue();
    $notification->to = '';

    $settings = Formie::$plugin->getSettings();
    $original = [$settings->sendEmailAlerts, $settings->alertEmails];
    $settings->sendEmailAlerts = true;
    $settings->alertEmails = [['Admin', 'alerts@example.test']];

    $sent = [];
    $capture = static function ($event) use (&$sent) {
        $sent[] = $event->message->getSymfonyEmail()->getTextBody();
        $event->isValid = false;
    };
    $mailer = Craft::$app->getMailer();
    $mailer->on(\yii\mail\BaseMailer::EVENT_BEFORE_SEND, $capture);

    try {
        Formie::$plugin->getNotifications()->sendNotificationEmail($notification, $submission);
    } finally {
        $mailer->off(\yii\mail\BaseMailer::EVENT_BEFORE_SEND, $capture);
        [$settings->sendEmailAlerts, $settings->alertEmails] = $original;
    }

    expect($sent)->toHaveCount(1)
        ->and($sent[0])->toContain('No recipient email address found');
});
