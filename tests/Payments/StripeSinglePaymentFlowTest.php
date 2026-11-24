<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\integrations\payments\Stripe;
use verbb\formie\models\PaymentDecision;

// Runs a single Stripe payment through processPayment() with the Stripe API stubbed, so the amount and currency sent to
// Stripe can be compared with the payment Formie records for them.
function stripeSinglePaymentFlow(array $providerSettings, array $values = []): array
{
    $client = new class extends \Stripe\StripeClient {
        public array $intents = [];

        public function __construct()
        {
            parent::__construct('sk_test_fixture');
        }

        public function __get($name)
        {
            $client = $this;

            return new class($client, (string)$name) {
                public function __construct(private object $client, private string $resource)
                {
                }

                public function create($payload, $options): object
                {
                    if ($this->resource !== 'paymentIntents') {
                        return \Stripe\Customer::constructFrom(['id' => 'cus_fixture']);
                    }

                    $this->client->intents[] = $payload;

                    return \Stripe\PaymentIntent::constructFrom([
                        'id' => 'pi_fixture_' . count($this->client->intents),
                        'client_secret' => 'pi_fixture_secret',
                        'amount' => $payload['amount'],
                        'currency' => strtolower((string)$payload['currency']),
                        'status' => 'requires_payment_method',
                    ]);
                }

                public function retrieve($id, $options = []): object
                {
                    return \Stripe\Customer::constructFrom(['id' => $id]);
                }
            };
        }
    };

    $integration = new class(['name' => 'Stripe flow', 'handle' => 'stripeFlow' . bin2hex(random_bytes(6)), 'publishableKey' => 'pk_test_flow', 'secretKey' => 'sk_test_flow']) extends Stripe {
        // Static, so the saved integration doesn't store the client as a setting.
        public static ?\Stripe\StripeClient $testClient = null;

        public function getStripe(): \Stripe\StripeClient
        {
            return self::$testClient;
        }
    };
    $integration::$testClient = $client;
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();

    $form = formie()->form(['title' => 'Stripe flow'])
        ->dropdownField('currency', ['options' => [
            ['label' => 'Norwegian krone', 'value' => 'nok', 'isDefault' => true],
            ['label' => 'US dollar', 'value' => 'usd'],
        ]])
        ->paymentField('payment', [
            'paymentIntegration' => $integration->handle,
            'paymentIntegrationType' => Stripe::class,
        ])->create();

    $field = $form->getFieldByHandle('payment');
    $field->providerSettings = [$integration->handle => array_map(
        fn($value) => $value === '{currencyField}' ? '{field:' . $form->getFieldByHandle('currency')->reference . '}' : $value,
        ['type' => 'single', ...$providerSettings],
    )];
    $integration->setField($field);

    $submission = formie()->submission($form)->with($values)->save();
    $decision = $integration->processPayment($submission);
    $payments = Formie::$plugin->getPayments()->getSubmissionPayments($submission);

    return ['decision' => $decision, 'intents' => $client->intents, 'payments' => $payments];
}

it('records the amount Stripe is asked to charge', function(string $currency, string $amount, int $stripeAmount, string $recorded) {
    $result = stripeSinglePaymentFlow(['amountType' => 'fixed', 'amountFixed' => $amount, 'currencyType' => 'fixed', 'currencyFixed' => $currency]);

    expect($result['intents'])->toHaveCount(1)
        ->and($result['intents'][0]['amount'])->toBe($stripeAmount)
        ->and($result['decision']->status)->toBe(PaymentDecision::STATUS_ACTION_REQUIRED)
        ->and($result['payments'])->toHaveCount(1)
        ->and($result['payments'][0]->currency)->toBe($currency)
        ->and($result['payments'][0]->getMoney()->decimal())->toBe($recorded)
        ->and($result['payments'][0]->reference)->toBe('pi_fixture_1');
})->with([
    'two decimals' => ['USD', '12.34', 1234, '12.34'],
    'zero decimals' => ['JPY', '500', 500, '500'],
    'three decimals ending in 0' => ['KWD', '1.230', 1230, '1.230'],
    'three decimals rounded down' => ['KWD', '1.234', 1230, '1.230'],
    'three decimals rounded up' => ['BHD', '1.235', 1240, '1.240'],
]);

it('takes a payment when the currency field holds a lowercase code', function() {
    $result = stripeSinglePaymentFlow(
        ['amountType' => 'fixed', 'amountFixed' => '12.50', 'currencyType' => 'dynamic', 'currencyVariable' => '{currencyField}'],
        ['currency' => 'nok'],
    );

    expect($result['intents'])->toHaveCount(1)
        ->and($result['intents'][0]['amount'])->toBe(1250)
        ->and(strtoupper((string)$result['intents'][0]['currency']))->toBe('NOK')
        ->and($result['decision']->status)->toBe(PaymentDecision::STATUS_ACTION_REQUIRED)
        ->and($result['payments'])->toHaveCount(1)
        ->and($result['payments'][0]->currency)->toBe('NOK')
        ->and($result['payments'][0]->reference)->toBe('pi_fixture_1');
});
