<?php

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Hidden;
use verbb\formie\helpers\References;
use verbb\formie\models\Notification;

it('exposes hidden fields to email variable pickers regardless of their default source', function (string $source): void {
    $field = new Hidden(['valueSource' => $source]);
    $primary = $field->getFieldTypeConfig(false)['referenceValues'][0];

    expect($primary['supportsVariablePicker'])->toBeTrue()
        ->and($primary['shape'])->toBe('inline')
        ->and($primary['types'])->toContain('text', 'email');
})->with(['custom', 'template', 'query', 'cookie', 'userEmail']);

it('uses a saved hidden field value as notification recipients', function (): void {
    $form = formie()->form()->hiddenField('toEmail')->create();
    $saved = formie()->submission($form)->with([
        'toEmail' => 'staff@example.test, other@example.test',
    ])->save();
    $submission = Submission::find()->id($saved->id)->status(null)->one();
    $notification = new Notification([
        'name' => 'Staff notification',
        'handle' => 'staffNotification',
        'to' => References::field($form->getFieldByHandle('toEmail')->reference),
        'from' => 'sender@example.test',
        'subject' => 'Staff enquiry',
        'content' => 'Enquiry received.',
    ]);

    $result = Formie::$plugin->getEmails()->renderEmail($notification, $submission);

    expect($result)->not->toHaveKey('error')
        ->and(array_keys($result['email']->getTo()))->toBe(['staff@example.test', 'other@example.test']);
});

it('rejects malformed and injected hidden recipients at the email boundary', function (string $value): void {
    $form = formie()->form()->hiddenField('toEmail')->create();
    $submission = new Submission();
    $submission->setForm($form);
    // Exercise actual recipient rendering with untrusted field values.
    $submission->setFieldValue('toEmail', $value);
    $notification = new Notification([
        'name' => 'Untrusted recipient',
        'handle' => 'untrustedRecipient',
        'to' => References::field($form->getFieldByHandle('toEmail')->reference),
        'from' => 'sender@example.test',
        'subject' => 'Staff enquiry',
        'content' => 'Enquiry received.',
    ]);
    $_SERVER['FORMIE_HIDDEN_RECIPIENT_SECRET'] = 'secret@example.test';

    try {
        $result = Formie::$plugin->getEmails()->renderEmail($notification, $submission);

        expect($result)->toHaveKey('error')
            ->and($result['email']->getTo())->toBeEmpty()
            ->and($result['email']->getBcc())->toBeEmpty();
    } finally {
        unset($_SERVER['FORMIE_HIDDEN_RECIPIENT_SECRET']);
    }
})->with([
    'invalid address' => 'not-an-email',
    'header injection' => "staff@example.test\r\nBcc: attacker@example.test",
    'environment variable' => '$FORMIE_HIDDEN_RECIPIENT_SECRET',
    'nested reference' => '{env:FORMIE_HIDDEN_RECIPIENT_SECRET}',
    'Twig expression' => '{{ 7 * 7 }}',
]);
