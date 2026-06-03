<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\References;
use verbb\formie\models\Notification;

it('extracts field handles from notification recipient settings', function (): void {
    $content = '{field:recipientEmail}, admin@example.test, {field:ccField|cc@example.test}';

    expect(References::extractFieldReferenceHandles($content))
        ->toBe(['recipientEmail', 'ccField']);
});

it('renders notification preview when To references a hidden field', function (): void {
    $form = formie()
        ->form(['title' => 'Notification Preview Field Recipient'])
        ->hiddenField('recipientEmail')
        ->create();

    $notification = new Notification([
        'name' => 'Preview Recipient',
        'handle' => 'previewRecipient' . uniqid(),
        'to' => References::field('recipientEmail'),
        'from' => 'sender@example.test',
        'subject' => 'Preview Subject',
        'content' => 'Preview body',
    ]);

    $submission = new Submission();
    $submission->setForm($form);

    Formie::$plugin->getSubmissions()->populateFakeSubmission($submission, $notification);

    $result = Formie::$plugin->getEmails()->renderEmail($notification, $submission);

    expect($result)->not->toHaveKey('error')
        ->and($result['email']->getTo())->not->toBeEmpty();
});

it('uses email preview values for field references in Cc and Bcc', function (): void {
    $form = formie()
        ->form(['title' => 'Notification Preview Cc Bcc'])
        ->singleLineTextField('ccEmail')
        ->singleLineTextField('bccEmail')
        ->create();

    $notification = new Notification([
        'name' => 'Preview Cc Bcc',
        'handle' => 'previewCcBcc' . uniqid(),
        'to' => 'recipient@example.test',
        'cc' => References::field('ccEmail'),
        'bcc' => References::field('bccEmail'),
        'from' => 'sender@example.test',
        'subject' => 'Preview Subject',
        'content' => 'Preview body',
    ]);

    $submission = new Submission();
    $submission->setForm($form);

    Formie::$plugin->getSubmissions()->populateFakeSubmission($submission, $notification);

    $result = Formie::$plugin->getEmails()->renderEmail($notification, $submission);

    expect($result)->not->toHaveKey('error')
        ->and($result['email']->getCc())->not->toBeEmpty()
        ->and($result['email']->getBcc())->not->toBeEmpty();
});
