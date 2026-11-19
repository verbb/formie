<?php

declare(strict_types=1);

use craft\models\GqlSchema;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\base\Payment as BasePayment;
use verbb\formie\events\PaymentIntegrationProcessEvent;
use verbb\formie\integrations\payments\Stripe;
use yii\base\Event;

// Client-rendered Payment fields show the provider markup server-rendered forms use, and their module's inputs must
// reach the field as a server-rendered post would.
it('sends the provider markup and maps client payment inputs like a server-rendered post', function(): void {
    $integration = new Stripe(['name' => 'Client payment', 'handle' => 'clientPayment' . bin2hex(random_bytes(4)), 'enabled' => true, 'publishableKey' => 'pk_test_client', 'secretKey' => 'sk_test_client']);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();

    $form = formie()->form()
        ->singleLineTextField('payerName', ['label' => 'Payer name'])
        ->paymentField('pay', ['label' => 'Pay', 'paymentIntegration' => $integration->handle, 'paymentIntegrationType' => Stripe::class, 'providerSettings' => [$integration->handle => [
            'amountType' => 'fixed', 'amountFixed' => '10', 'currencyType' => 'fixed', 'currencyFixed' => 'USD',
        ]]])
        ->settings(['disableCaptchas' => true])
        ->create();
    $captured = null;
    $capture = static function(PaymentIntegrationProcessEvent $event) use (&$captured): void {
        $field = $event->integration->getField();
        $captured = $field->normalizeValue($event->submission->getFieldValue($field->valueKey()), $event->submission)->getAttributes();
        // Stop before the provider is called.
        $event->isValid = false;
    };
    Event::on(Stripe::class, BasePayment::EVENT_BEFORE_PROCESS_PAYMENT, $capture);

    try {
        WebRequestTestHelper::withWebRequestContext(function() use ($form, &$captured): void {
            $gql = Craft::$app->getGql();
            $schema = new GqlSchema(['name' => 'Payment client definition', 'scope' => [
                'formieForms.' . $form->uid . ':read',
                'formieSubmissions.' . $form->uid . ':create',
            ]]);
            $loaded = $gql->executeQuery($schema, 'query($handle: String!) {
                formieClientForm(handle: $handle) { definition session { id version currentPageId tokens continuation } }
            }', ['handle' => $form->handle]);

            expect($loaded)->not->toHaveKey('errors');
            $bootstrap = $loaded['data']['formieClientForm'];
            $fields = array_merge(...array_map(static fn(array $row): array => $row['fields'], $bootstrap['definition']['pages'][0]['rows']));
            $payment = current(array_filter($fields, static fn(array $field): bool => $field['handle'] === 'pay'));

            expect($payment['type'])->toBe('payment')
                ->and($payment['input']['html'])->toBe((string)$form->getFieldByHandle('pay')->getPaymentHtml())
                ->and($payment['input']['html'])->toContain('data-formie-stripe-elements')
                ->and($payment['input']['html'])->toContain('name="fields[pay][stripePaymentIntentId]"');

            $submitted = $gql->executeQuery($schema, 'mutation($input: FormieClientSubmitInput!) {
                submitFormieClientForm(input: $input) { outcome success errors }
            }', ['input' => [
                'handle' => $form->handle,
                'session' => $bootstrap['session'],
                'values' => ['payerName' => 'Ada'],
                'browserData' => ['fields[pay]' => '', 'fields[pay][stripePaymentIntentId]' => 'pi_client', 'fields[pay][stripeSubscriptionId]' => ''],
            ]]);

            expect($submitted)->not->toHaveKey('errors');
            $field = $form->getFieldByHandle('pay');
            $serverPost = $field->normalizeValue(['stripePaymentIntentId' => 'pi_client', 'stripeSubscriptionId' => ''], null)->getAttributes();

            expect($captured)->not->toBeNull()
                ->and($captured)->toBe($serverPost)
                ->and($captured['stripePaymentIntentId'] ?? null)->toBe('pi_client');
        }, ['method' => 'POST']);
    } finally {
        Event::off(Stripe::class, BasePayment::EVENT_BEFORE_PROCESS_PAYMENT, $capture);
        Craft::$app->getElements()->deleteElement($form, true);
        Formie::$plugin->getIntegrations()->deleteIntegrationById($integration->id);
    }
});
