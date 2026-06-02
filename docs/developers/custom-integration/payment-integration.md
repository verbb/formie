# Payment Integration

Payment integrations extend `Payment`. They are used by Payment fields to collect payment details, authorize or tokenize payment information on the front end, then complete the payment on the server while Formie processes the submission.

Payment integrations are field-driven. A user creates the payment integration in Formie’s plugin settings, adds a Payment field to a form, then chooses that provider in the Payment field’s settings.

The front-end and back-end pieces work together:

1. The user creates and configures the payment provider in Formie’s plugin settings.
2. The user adds a Payment field to a form and selects that provider.
3. `renderFieldHtml()` renders the provider’s Payment field template.
4. `getClientModule()` registers any front-end module the provider needs.
5. The front-end module mounts the provider UI and writes the token, payment id or authorization value into hidden Payment field inputs.
6. During the authorize stage, the module can block submission if the payment UI has not produced the required value.
7. `processPayment()` reads the Payment field payload, calls the provider API and returns a `PaymentDecision`.
8. If the provider uses redirects, callbacks or webhooks, the integration handles the follow-up provider response and updates the payment record.

## PHP Integration

```php
use Craft;
use craft\helpers\App;
use craft\helpers\Json;
use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Payment;
use verbb\formie\elements\Submission;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\models\PaymentDecision;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;
use Throwable;

class ExamplePayment extends Payment
{
    public ?string $publishableKey = null;
    public ?string $secretKey = null;

    public static function displayName(): string
    {
        return Craft::t('formie', 'Example Payment');
    }

    public function hasValidSettings(): bool
    {
        return App::parseEnv($this->publishableKey) && App::parseEnv($this->secretKey);
    }

    public function getClientModule(ClientModuleContext $context): ?ClientModule
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        $this->setField($context->field);

        return new ClientModule([
            'id' => 'example-payment',
            'src' => '/assets/formie/example-payment.js',
            'config' => [
                'publishableKey' => App::parseEnv($this->publishableKey),
                'amountType' => $this->getFieldSetting('amountType'),
                'amountFixed' => $this->getFieldSetting('amountFixed'),
                'amountVariable' => $this->normalizeClientFieldReference($this->getFieldSetting('amountVariable')),
                'currency' => $this->getFieldSetting('currency'),
                'requiredInputSuffixes' => ['examplePaymentToken'],
                'waitForValueMs' => 2500,
            ],
        ]);
    }

    public function processPayment(Submission $submission): PaymentDecision
    {
        if (!$this->beforeProcessPayment($submission)) {
            return PaymentDecision::notRequired();
        }

        $field = $this->getField();
        $payload = $this->getPaymentFieldPayload($submission);
        $token = $payload->string('examplePaymentToken');
        $amount = $this->getAmount($submission);
        $currency = $this->getCurrency($submission);

        if (!$field || !$token || !$amount || !$currency) {
            $message = Craft::t('formie', 'Payment details are incomplete.');
            $this->addFieldError($submission, $message);

            return PaymentDecision::failed($message, $this->handle);
        }

        try {
            $response = $this->request('POST', 'https://payments.example.test/v1/payments', [
                'json' => [
                    'token' => $token,
                    'amount' => $amount,
                    'currency' => $currency,
                ],
            ]);

            $payment = new PaymentModel();
            $payment->integrationId = $this->id;
            $payment->submissionId = $submission->id;
            $payment->fieldId = $field->id;
            $payment->amount = $amount;
            $payment->currency = $currency;
            $payment->status = PaymentModel::STATUS_SUCCESS;
            $payment->reference = $response['id'] ?? '';
            $payment->response = $response;

            Formie::$plugin->getPayments()->savePayment($payment);
            $this->afterProcessPayment($submission, true);

            return PaymentDecision::succeeded($this->handle, $payment->reference);
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            $message = $this->getFriendlyPaymentErrorMessage($e);
            $this->addFieldError($submission, $message);
            $this->afterProcessPayment($submission, false);

            return PaymentDecision::failed($message, $this->handle);
        }
    }
}
```

This example keeps the provider flow simple: the browser module creates a provider token, `processPayment()` reads that token and the server captures the payment. Redirect-style providers usually return `PaymentDecision::actionRequired()` or `PaymentDecision::pending()` and finish through a callback or webhook.

## Payment Field Template

Payment integrations render their field template from `integrations/payments/{handle}/field`. The exact markup depends on the provider, but most provider templates include a hidden base input for field errors, one or more hidden transport inputs, and a placeholder for the provider UI.

```twig
{{ fieldtag('fieldInput') }}

<input type="hidden" name="{{ field.getHtmlName() }}">
<input type="hidden" name="{{ field.getHtmlName('examplePaymentToken') }}">

<div data-example-payment-card></div>
```

The hidden input suffix should match the value written by the front-end module and the value read in `processPayment()`.

## Client Modules

Payment provider modules should usually use `definePaymentModule()` from `@verbb/formie-browser`. It gives you shared services for updating hidden payment inputs, showing payment errors, resolving dynamic amounts and currencies, and participating in Formie’s authorize stage.

```ts
import { definePaymentModule } from '@verbb/formie-browser';

type ExamplePaymentApi = {
  mountCard: (container: HTMLElement, options: Record<string, unknown>) => {
    tokenize: () => Promise<{ ok: boolean; token?: string; message?: string }>;
    destroy: () => void;
  };
};

type ExamplePaymentOptions = {
  publishableKey?: string | null;
  amountType?: string | null;
  amountFixed?: string | number | null;
  amountVariable?: string | null;
  currency?: string | null;
};

declare global {
  interface Window {
    ExamplePayment?: ExamplePaymentApi;
  }
}

async function loadExamplePaymentApi(): Promise<ExamplePaymentApi> {
  if (window.ExamplePayment) {
    return window.ExamplePayment;
  }

  await new Promise<void>((resolve, reject) => {
    const script = document.createElement('script');

    script.src = 'https://payments.example.test/sdk.js';
    script.async = true;
    script.onload = () => resolve();
    script.onerror = () => reject(new Error('Unable to load Example Payment.'));
    document.head.appendChild(script);
  });

  if (!window.ExamplePayment) {
    throw new Error('Example Payment API was not available after loading.');
  }

  return window.ExamplePayment;
}

export default definePaymentModule<ExamplePaymentOptions, ExamplePaymentApi, ReturnType<ExamplePaymentApi['mountCard']>>({
  id: 'example-payment',
  defaultRequiredInputSuffixes: ['examplePaymentToken'],

  load: () => {
    return loadExamplePaymentApi();
  },

  mount: ({ api, field, services, provider }) => {
    const container = field.querySelector<HTMLElement>('[data-example-payment-card]');
    const amount = services.resolveAmount({
      type: provider.amountType,
      fixed: provider.amountFixed,
      variable: provider.amountVariable,
    });

    if (!container || !provider.publishableKey) {
      throw new Error('Example Payment is not configured.');
    }

    if (!amount.ok) {
      services.addError(amount.error);
    }

    return api.mountCard(container, {
      publishableKey: provider.publishableKey,
      amount: amount.ok ? amount.value : null,
      currency: provider.currency || 'AUD',
    });
  },

  onBeforeAuthorize: async ({ widget, services }) => {
    if (!widget) {
      services.addError('Payment form is not ready.');

      return false;
    }

    const result = await widget.tokenize();

    if (result.ok && result.token) {
      services.updateInputs('examplePaymentToken', result.token);

      return true;
    }

    services.addError(result.message || 'Payment could not be authorized.');

    return false;
  },

  onAfterSubmit: async ({ services }) => {
    services.updateInputs('examplePaymentToken', '');
  },

  unmount: ({ widget }) => {
    widget.destroy();
  },
});
```

`onBeforeAuthorize()` runs before Formie dispatches the submission. Use it when the provider needs to tokenize card details, confirm a wallet payment or produce an id that the server must receive. Return `false` to stop submission and keep the user on the form.

## Methods

Payment integrations commonly use these methods:

Method | Use
--- | ---
`renderFieldHtml()` | Renders the provider’s Payment field template.
`getClientModule()` | Registers front-end behavior for the Payment field.
`processPayment()` | Captures or creates the payment after Formie receives the submission.
`getAmount()` | Resolves the configured fixed or dynamic amount from the Payment field.
`getCurrency()` | Resolves the configured fixed or dynamic currency from the Payment field.
`getPaymentFieldPayload()` | Reads provider-specific hidden input values from the submitted Payment field.
`addFieldError()` | Adds an error to the Payment field when processing fails.
`supportsWebhooks()` | Whether the provider can send webhook updates.
`supportsCallbacks()` | Whether the provider redirects back to Formie after off-site payment steps.
`requiresAjaxSubmission()` | Whether forms using this provider must submit with Ajax.
`getRedirectUri()` | Returns the webhook URL Formie exposes for the payment provider.
