<?php

use verbb\formie\elements\Submission;
use verbb\formie\fields\Payment as PaymentField;
use verbb\formie\integrations\payments\Stripe;
use verbb\formie\models\Payment;

it('exports the latest payment details for its field', function(): void {
    $field = new PaymentField(['id' => 42, 'label' => 'Payment', 'handle' => 'payment']);
    $integration = new Stripe();
    $older = new Payment(['fieldId' => 42, 'amount' => '5.00', 'currency' => 'aud']);
    $payment = new Payment([
        'fieldId' => 42,
        'subscriptionId' => 7,
        'amount' => '25.50',
        'currency' => 'aud',
        'status' => Payment::STATUS_SUCCEEDED,
        'reference' => 'txn_123',
        'dateCreated' => new DateTime('2026-10-14T09:30:00+11:00'),
    ]);
    $integrationProperty = new ReflectionProperty(Payment::class, '_integration');
    $integrationProperty->setValue($payment, $integration);

    $submission = new class ([$older, $payment]) extends Submission {
        public function __construct(private readonly array $testPayments)
        {
            parent::__construct();
        }

        public function getPayments(): ?array
        {
            return $this->testPayments;
        }

        public function getFields(): array
        {
            return [];
        }
    };

    expect($field->getValueForExport(null, $submission))->toBe([
        'Payment: Provider' => 'Stripe',
        'Payment: Type' => 'Subscription',
        'Payment: Status' => 'Succeeded',
        'Payment: Amount' => '25.50',
        'Payment: Currency' => 'AUD',
        'Payment: Date' => '2026-10-14T09:30:00+11:00',
        'Payment: Transaction Reference' => 'txn_123',
    ]);
});

it('exports empty payment columns when no matching payment exists', function(): void {
    $field = new PaymentField(['id' => 42, 'label' => 'Payment', 'handle' => 'payment']);

    expect($field->getValueForExport(null))->toBe([
        'Payment: Provider' => '',
        'Payment: Type' => '',
        'Payment: Status' => '',
        'Payment: Amount' => '',
        'Payment: Currency' => '',
        'Payment: Date' => '',
        'Payment: Transaction Reference' => '',
    ]);
});
