<?php

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\References;
use verbb\formie\integrations\payments\Stripe;
use verbb\formie\models\Notification;
use verbb\formie\models\Payment;
use verbb\formie\references\ReferenceOutputContext;

function wholeFieldEmail(Submission $submission, string $content, array $config = []): array
{
    return Formie::$plugin->getEmails()->renderEmail(new Notification([
        'name' => 'Whole field',
        'handle' => 'wholeField',
        'to' => 'staff@example.test',
        'from' => 'sender@example.test',
        'subject' => 'Enquiry',
        'content' => $content,
        ...$config,
    ]), Submission::find()->id($submission->id)->status(null)->one());
}

it('renders a whole Table reference as its table in the email body', function (): void {
    $form = formie()->form()->tableField('dates', ['columns' => [
        'col1' => ['heading' => 'Sacrament', 'handle' => 'sacrament', 'type' => 'singleline'],
        'col2' => ['heading' => 'Date', 'handle' => 'date', 'type' => 'singleline'],
    ]])->create();
    $submission = formie()->submission($form)->with(['dates' => [['col1' => 'Baptism', 'col2' => '2001']]])->save();

    $result = wholeFieldEmail($submission, 'Details: ' . References::field((string)$form->getFieldByHandle('dates')->reference));
    $html = $result['email']->getSymfonyEmail()->getHtmlBody();

    expect($result)->not->toHaveKey('error')
        ->and($html)->toContain('<table')
        ->and($html)->toContain('Sacrament')
        ->and($html)->toContain('Baptism')
        ->and($html)->toContain('2001');
});

it('renders a whole Repeater reference as its rows in the email body', function (): void {
    $form = formie()->form()->repeaterField('people', ['rows' => [['fields' => [[
        'type' => SingleLineText::class,
        'handle' => 'person',
        'label' => 'Person',
    ]]]]])->create();
    $submission = formie()->submission($form)->with(['people' => [['person' => 'Ada'], ['person' => 'Grace']]])->save();

    $result = wholeFieldEmail($submission, 'Who: ' . References::field((string)$form->getFieldByHandle('people')->reference));
    $text = $result['email']->getSymfonyEmail()->getTextBody();

    expect($result)->not->toHaveKey('error')
        ->and($text)->toContain('Ada')
        ->and($text)->toContain('Grace');
});

it('renders a whole Payment reference as its details instead of provider data', function (): void {
    $integration = new Stripe(['name' => 'Whole Field', 'handle' => 'wholeField' . bin2hex(random_bytes(5)), 'enabled' => true]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $form = formie()->form()->paymentField('payment', ['paymentIntegration' => $integration->handle, 'paymentIntegrationType' => Stripe::class])->create();
    $submission = formie()->submission($form)->with(['payment' => ['stripePaymentId' => 'pm_private']])->save();
    $field = $form->getFieldByHandle('payment');
    $payment = new Payment(['integrationId' => $integration->id, 'submissionId' => $submission->id, 'fieldId' => $field->id, 'amount' => '42.75', 'currency' => 'AUD', 'status' => Payment::STATUS_SUCCESS, 'reference' => 'pi_reference']);
    expect(Formie::$plugin->getPayments()->savePayment($payment))->toBeTrue();

    $result = wholeFieldEmail($submission, 'Paid: ' . References::field((string)$field->reference));
    $text = $result['email']->getSymfonyEmail()->getTextBody();

    expect($result)->not->toHaveKey('error')
        ->and($text)->toContain('pi_reference')
        ->and($text)->toContain('42.75')
        ->and($text)->not->toContain('pm_private')
        ->and($text)->not->toContain('stripePaymentId');
});

it('escapes submitted Table and Repeater values in a whole reference', function (): void {
    $form = formie()->form()->tableField('dates', ['columns' => [
        'col1' => ['heading' => 'Sacrament', 'handle' => 'sacrament', 'type' => 'singleline'],
    ]])->create();
    $submission = formie()->submission($form)->with(['dates' => [['col1' => '<img src=x onerror=alert(1)>']]])->save();

    $result = wholeFieldEmail($submission, 'Details: ' . References::field((string)$form->getFieldByHandle('dates')->reference));

    expect($result)->not->toHaveKey('error')
        ->and($result['email']->getSymfonyEmail()->getHtmlBody())->not->toContain('<img src=x');
});
