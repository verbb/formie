<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\models\Notification;

it('renders notification preview when from name and from email are set', function (): void {
    $form = formie()
        ->form(['title' => 'Notification From Name Preview'])
        ->create();

    $notification = new Notification([
        'name' => 'From Name Preview',
        'handle' => 'fromNamePreview' . uniqid(),
        'to' => 'recipient@example.test',
        'from' => 'sender@example.test',
        'fromName' => 'Example Sender',
        'subject' => 'Preview Subject',
        'content' => 'Preview body',
    ]);

    $submission = new Submission();
    $submission->setForm($form);

    Formie::$plugin->getSubmissions()->populateFakeSubmission($submission, $notification);

    $result = Formie::$plugin->getEmails()->renderEmail($notification, $submission);

    expect($result)->not->toHaveKey('error')
        ->and($result['email']->getFrom())->toBe(['sender@example.test' => 'Example Sender']);
});
