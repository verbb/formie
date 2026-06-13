<?php

declare(strict_types=1);

use verbb\formie\conditions\ConditionOperator;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\models\Notification;
use verbb\formie\models\Status;
use verbb\formie\services\Notifications;

use yii\base\Event;

function notificationTriggersSubmissionWithStatusChange(Status $previous, Status $current): Submission
{
    $submission = new Submission();
    $submission->id = 7001;
    $submission->isNewSubmission = false;
    $submission->setStatus($current);

    $reflection = new \ReflectionClass($submission);
    $property = $reflection->getProperty('_previousStatusId');
    $property->setAccessible(true);
    $property->setValue($submission, $previous->id);

    return $submission;
}

function notificationTriggersStatus(string $handle, string $name = 'Status'): Status
{
    return new Status([
        'name' => $name,
        'handle' => $handle,
        'color' => 'green',
    ]);
}

it('sends notifications configured for the new submission status', function (): void {
    $accepted = notificationTriggersStatus('accepted', 'Accepted');
    $new = notificationTriggersStatus('new', 'New');

    $form = new Form();
    $form->id = 7002;
    $form->setNotifications([
        new Notification([
            'name' => 'Accepted alert',
            'handle' => 'acceptedAlert',
            'enabled' => true,
            'subject' => 'Accepted',
            'recipients' => 'email@example.test',
            'conditions' => [
                'conditionRule' => 'all',
                'conditions' => [[
                    'field' => '{submission:status}',
                    'condition' => ConditionOperator::EQ,
                    'value' => 'accepted',
                ]],
            ],
        ]),
        new Notification([
            'name' => 'New alert',
            'handle' => 'newAlert',
            'enabled' => true,
            'subject' => 'New',
            'recipients' => 'email@example.test',
            'conditions' => [
                'conditionRule' => 'all',
                'conditions' => [[
                    'field' => '{submission:status}',
                    'condition' => ConditionOperator::EQ,
                    'value' => 'new',
                ]],
            ],
        ]),
    ]);

    $submission = notificationTriggersSubmissionWithStatusChange($new, $accepted);
    $submission->setForm($form);

    $sent = [];
    $handler = function ($event) use (&$sent): void {
        $sent[] = $event->notification->handle ?? null;
    };

    Event::on(Notifications::class, Notifications::EVENT_BEFORE_SEND_NOTIFICATION, $handler);

    try {
        Formie::$plugin->getNotificationTriggers()->dispatchStatusChange($submission);

        expect($sent)->toBe(['acceptedAlert']);
    } finally {
        Event::off(Notifications::class, Notifications::EVENT_BEFORE_SEND_NOTIFICATION, $handler);
    }
});

it('does not send status-change notifications for initial submissions', function (): void {
    $accepted = notificationTriggersStatus('accepted');

    $form = new Form();
    $form->id = 7003;
    $form->setNotifications([
        new Notification([
            'name' => 'Accepted alert',
            'handle' => 'acceptedAlert',
            'enabled' => true,
            'subject' => 'Accepted',
            'recipients' => 'email@example.test',
            'conditions' => [
                'conditionRule' => 'all',
                'conditions' => [[
                    'field' => '{submission:status}',
                    'condition' => ConditionOperator::EQ,
                    'value' => 'accepted',
                ]],
            ],
        ]),
    ]);

    $submission = new Submission();
    $submission->id = 7004;
    $submission->isNewSubmission = true;
    $submission->setForm($form);
    $submission->setStatus($accepted);

    $sent = 0;
    $handler = function () use (&$sent): void {
        $sent++;
    };

    Event::on(Notifications::class, Notifications::EVENT_BEFORE_SEND_NOTIFICATION, $handler);

    try {
        Formie::$plugin->getNotificationTriggers()->dispatchStatusChange($submission);

        expect($sent)->toBe(0);
    } finally {
        Event::off(Notifications::class, Notifications::EVENT_BEFORE_SEND_NOTIFICATION, $handler);
    }
});

it('does not send status-change notifications when the status is unchanged', function (): void {
    $accepted = notificationTriggersStatus('accepted');

    $form = new Form();
    $form->id = 7005;
    $form->setNotifications([
        new Notification([
            'name' => 'Accepted alert',
            'handle' => 'acceptedAlert',
            'enabled' => true,
            'subject' => 'Accepted',
            'recipients' => 'email@example.test',
            'conditions' => [
                'conditionRule' => 'all',
                'conditions' => [[
                    'field' => '{submission:status}',
                    'condition' => ConditionOperator::EQ,
                    'value' => 'accepted',
                ]],
            ],
        ]),
    ]);

    $submission = notificationTriggersSubmissionWithStatusChange($accepted, $accepted);
    $submission->setForm($form);

    $sent = 0;
    $handler = function () use (&$sent): void {
        $sent++;
    };

    Event::on(Notifications::class, Notifications::EVENT_BEFORE_SEND_NOTIFICATION, $handler);

    try {
        Formie::$plugin->getNotificationTriggers()->dispatchStatusChange($submission);

        expect($sent)->toBe(0);
    } finally {
        Event::off(Notifications::class, Notifications::EVENT_BEFORE_SEND_NOTIFICATION, $handler);
    }
});
