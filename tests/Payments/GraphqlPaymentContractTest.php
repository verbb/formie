<?php

declare(strict_types=1);

use verbb\formie\client\models\SubmitResult;
use verbb\formie\Formie;
use verbb\formie\gql\types\input\PaymentInputType;
use verbb\formie\integrations\payments\Stripe;
use verbb\formie\models\PaymentDecision;
use verbb\formie\models\SubmissionResponse;
use verbb\formie\services\SubmissionRequests;
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
        'payment' => [
            'status' => PaymentDecision::STATUS_ACTION_REQUIRED->value,
            'message' => 'Confirm your payment.',
            'action' => [
            'type' => 'confirm',
            'provider' => 'stripe',
            'payload' => ['clientSecret' => 'pi_secret'],
        ],
        ],
    ]);

    $payload = $result->toArrayRecursive();

    expect($payload['payment']['status'])->toBe('requiresAction')
        ->and($payload['payment']['message'])->toBe('Confirm your payment.')
        ->and($payload['payment']['action']['type'])->toBe('confirm')
        ->and($payload)->not->toHaveKeys(['paymentStatus', 'paymentDecision', 'paymentAction', 'keepSubmitLoading']);
});

it('maps submission payment responses onto client submit result fields', function(): void {
    $processor = Formie::$plugin->getSubmissionRequests();
    $method = new ReflectionMethod(SubmissionRequests::class, '_resolvePaymentSubmitResultFields');
    $method->setAccessible(true);

    $fields = $method->invoke($processor, new SubmissionResponse([
        'payment' => ['status' => PaymentDecision::STATUS_PENDING->value, 'message' => 'Waiting for payment confirmation.', 'action' => null],
    ]));

    expect($fields)->toMatchArray([
        'payment' => ['status' => 'pending', 'message' => 'Waiting for payment confirmation.', 'action' => null],
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

it('preserves unknown and cancellation payment detail in shared transport results', function (string $status, bool $loading): void {
    $method = new ReflectionMethod(SubmissionRequests::class, '_resolvePaymentSubmitResultFields');
    $fields = $method->invoke(Formie::$plugin->getSubmissionRequests(), new SubmissionResponse([
        'payment' => ['status' => $status, 'message' => 'Provider outcome'],
    ]));
    expect($fields['payment']['status'])->toBe($status)->and($fields)->not->toHaveKey('keepSubmitLoading');
})->with([['unknown', true], ['cancelled', false]]);
