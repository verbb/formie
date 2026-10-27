<?php

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\References;
use verbb\formie\models\Notification;

it('renders matching recipients from saved notification rules', function (bool $legacy, string $choice, array $expected): void {
    $form = formie()->form()->dropdownField('department', [
        'options' => [
            ['label' => 'Sales team', 'value' => 'sales'],
            ['label' => 'Support team', 'value' => 'support'],
            ['label' => 'Other', 'value' => 'other'],
        ],
    ])->hiddenField('recipientEmail')->create();
    $reference = References::field($form->getFieldByHandle('department')->reference);
    $rules = [
        ['email' => 'Sales@Example.test', 'field' => $reference, 'condition' => '=', 'value' => 'sales'],
        ['email' => References::field($form->getFieldByHandle('recipientEmail')->reference), 'field' => $reference, 'condition' => '=', 'value' => 'sales'],
        ['email' => 'support@example.test', 'field' => $reference, 'condition' => '=', 'value' => 'support'],
    ];
    $notification = new Notification([
        'formId' => $form->id,
        'name' => 'Conditional recipient',
        'handle' => 'conditionalRecipient',
        'enabled' => true,
        'recipients' => Notification::RECIPIENTS_CONDITIONS,
        'toConditions' => $legacy ? ['toRecipients' => $rules] : $rules,
        'from' => 'sender@example.test',
        'subject' => 'Conditional recipient',
        'content' => 'Submission received.',
    ]);
    $notifications = Formie::$plugin->getNotifications();
    expect($notifications->saveNotification($notification))->toBeTrue();
    $notification = $notifications->getNotificationById($notification->id);
    $saved = formie()->submission($form)->with([
        'department' => $choice,
        'recipientEmail' => 'additional@example.test',
    ])->save();
    $submission = Submission::find()->id($saved->id)->status(null)->one();

    // Render the actual email from reloaded records without sending any mail.
    $result = Formie::$plugin->getEmails()->renderEmail($notification, $submission);

    if ($expected) {
        expect($result)->not->toHaveKey('error')
            ->and(array_keys($result['email']->getTo()))->toBe($expected);
    } else {
        expect($result['error'])->toBe('Notification email error. No recipient email address found.')
            ->and($result['email']->getTo())->toBeEmpty();
    }
})->with([
    'current multiple matches' => [false, 'sales', ['sales@example.test', 'additional@example.test']],
    'current single match' => [false, 'support', ['support@example.test']],
    'current no match' => [false, 'other', []],
    'legacy multiple matches' => [true, 'sales', ['sales@example.test', 'additional@example.test']],
    'legacy single match' => [true, 'support', ['support@example.test']],
    'legacy no match' => [true, 'other', []],
]);

it('does not select a recipient from empty conditions', function (?array $conditions): void {
    $notification = new Notification([
        'recipients' => Notification::RECIPIENTS_CONDITIONS,
        'toConditions' => $conditions,
    ]);

    expect($notification->getToEmail(new Submission()))->toBeFalsy();
})->with([
    'absent' => [null],
    'empty list' => [[]],
    'empty legacy wrapper' => [['toRecipients' => []]],
]);
