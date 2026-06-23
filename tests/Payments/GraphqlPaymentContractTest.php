<?php

declare(strict_types=1);

use verbb\formie\client\models\SubmitResult;
use verbb\formie\Formie;
use verbb\formie\gql\types\input\PaymentInputType;
use verbb\formie\integrations\payments\Stripe;
use verbb\formie\models\PaymentDecision;
use verbb\formie\models\SubmissionResponse;
use verbb\formie\services\SubmissionProcessor;
use verbb\formie\fields\Payment as PaymentField;

use GraphQL\Type\Definition\InputObjectType;

function createStripeGraphqlPaymentFixture(): array
{
    $integration = new Stripe([
        'name' => 'Stripe GraphQL ' . uniqid(),
        'handle' => 'stripeGraphql' . uniqid(),
        'publishableKey' => 'pk_test_graphql',
        'secretKey' => 'sk_test_graphql',
    ]);
    Formie::$plugin->getIntegrations()->saveIntegration($integration, false);

    $form = formie()
        ->form(['title' => 'GraphQL Payment ' . uniqid()])
        ->paymentField('payment', [
            'paymentIntegration' => $integration->handle,
            'paymentIntegrationType' => get_class($integration),
        ])
        ->create();

    return [$form, $integration];
}

it('serializes payment follow-up fields on submit results', function(): void {
    $result = new SubmitResult([
        'success' => false,
        'paymentStatus' => PaymentDecision::STATUS_ACTION_REQUIRED,
        'paymentMessage' => 'Confirm your payment.',
        'paymentAction' => [
            'type' => 'confirm',
            'provider' => 'stripe',
            'payload' => ['clientSecret' => 'pi_secret'],
        ],
        'paymentDecision' => [
            'status' => PaymentDecision::STATUS_ACTION_REQUIRED,
        ],
        'keepSubmitLoading' => true,
    ]);

    $payload = $result->toArrayRecursive();

    expect($payload['paymentStatus'])->toBe('actionRequired')
        ->and($payload['paymentMessage'])->toBe('Confirm your payment.')
        ->and($payload['paymentAction']['type'])->toBe('confirm')
        ->and($payload['keepSubmitLoading'])->toBeTrue();
});

it('maps submission payment responses onto client submit result fields', function(): void {
    $processor = Formie::$plugin->getSubmissionProcessor();
    $method = new ReflectionMethod(SubmissionProcessor::class, '_resolvePaymentSubmitResultFields');
    $method->setAccessible(true);

    $fields = $method->invoke($processor, new SubmissionResponse([
        'paymentStatus' => PaymentDecision::STATUS_PENDING,
        'paymentMessage' => 'Waiting for payment confirmation.',
        'paymentRedirectUrl' => 'https://example.test/pay',
        'paymentAction' => ['type' => 'redirect'],
        'paymentDecision' => ['status' => PaymentDecision::STATUS_PENDING],
    ]));

    expect($fields)->toMatchArray([
        'paymentStatus' => 'pending',
        'paymentMessage' => 'Waiting for payment confirmation.',
        'paymentRedirectUrl' => 'https://example.test/pay',
        'paymentAction' => ['type' => 'redirect'],
        'paymentDecision' => ['status' => 'pending'],
        'keepSubmitLoading' => true,
    ]);
});

it('generates provider-specific payment input types for graphql', function(): void {
    [$form] = createStripeGraphqlPaymentFixture();

    /** @var PaymentField $paymentField */
    $paymentField = $form->getFieldByHandle('payment');

    $inputType = PaymentInputType::getType($paymentField);

    expect($inputType)->toBeInstanceOf(InputObjectType::class);

    $fieldNames = array_keys($inputType->getFields());

    expect($fieldNames)->toContain('stripePaymentIntentId', 'stripePaymentId', 'stripeSubscriptionId');
});

it('declares stripe graphql payment input keys on the integration', function(): void {
    [$form, $integration] = createStripeGraphqlPaymentFixture();
    $field = $form->getFieldByHandle('payment');

    expect($integration->getGraphqlPaymentInputFieldKeys($field))
        ->toContain('stripePaymentIntentId', 'stripePaymentId', 'stripeSubscriptionId');
});
