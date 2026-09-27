# Payment Integration

Payment integrations extend `Payment`. They are used by Payment fields to collect payment details, authorize or tokenize payment information in the browser, then complete the payment on the server while Formie processes the submission.

Payment integrations are field-driven. A user creates the payment integration in Formie’s plugin settings, adds a Payment field to a form, then chooses that provider in the Payment field’s settings.

The browser and server pieces work together:

1. The user creates and configures the payment provider in Formie’s plugin settings.
2. The user adds a Payment field to a form and selects that provider.
3. `renderFieldHtml()` renders the provider’s Payment field template.
4. `getBrowserModule()` registers any browser module the provider needs.
5. The browser module mounts the provider UI and writes the token, payment id or authorisation value into hidden Payment field inputs.
6. During the browser payment stage, the module can block submission if the payment UI has not produced the required value.
7. The inherited `processPayment()` establishes the durable attempt and lock, then calls your protected `executePayment()` method, which returns a typed `PaymentDecision`.
8. If the provider uses redirects, challenges or webhooks, the integration handles the follow-up provider response and updates the payment record.

When a saved payment completes through reconciliation or a verified webhook, Formie compares its amount and currency with the current submission before completing the form. `getPaymentAmount($submission)` must return the amount in the major currency units stored on the payment record. Its default implementation calls `getAmount()`; override it if your provider’s `getAmount()` returns minor API units. `getCurrency($submission)` resolves the configured fixed or dynamic currency, falling back to the field’s `currency` setting.

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
use verbb\formie\models\BrowserModuleEntry;
use verbb\formie\models\BrowserModuleContext;
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

    public function getBrowserModule(BrowserModuleContext $context): ?BrowserModuleEntry
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        $this->setField($context->field);

        return new BrowserModuleEntry([
            'moduleId' => 'example:example-payment',
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

    protected function executePayment(Submission $submission): PaymentDecision
    {
        if (!$this->beforeProcessPayment($submission)) {
            return PaymentDecision::notRequired();
        }

        $token = $this->getPaymentFieldPayload($submission)->string('examplePaymentToken');
        if (!$token) {
            return PaymentDecision::failed('Payment details are incomplete.', $this->handle);
        }

        return \verbb\formie\helpers\PaymentAttempt::run(
            $this,
            $submission,
            $this->getPaymentAmount($submission),
            $this->getCurrency($submission),
            ['secretKey' => $this->secretKey],
            function (PaymentModel $payment, \verbb\formie\helpers\PaymentAttempt $attempt) use ($token, $submission): PaymentDecision {
                $money = \verbb\formie\models\PaymentMoney::fromDecimal($payment->amount, $payment->currency);
                $payload = ['token' => $token, 'amount' => $money->minor, 'currency' => $money->currency];
                $response = $attempt->request(
                    $payload,
                    fn() => $this->request('POST', 'https://payments.example.test/v1/payments', ['json' => $payload]),
                    fn(array $result) => $result['id'] ?? null,
                );
                // Verify the provider's identity, status, amount and currency here.
                // This example API returns integer minor-unit strings.
                if (($response['status'] ?? null) !== 'paid'
                    || !\verbb\formie\models\PaymentMoney::fromMinor($response['amount'], $response['currency'])->equals($money)) {
                    return PaymentDecision::unknown('Payment needs reconciliation.', $this->handle);
                }
                $payment->reference = $response['id'];
                $payment->response = $response;
                $payment->status = PaymentModel::STATUS_SUCCESS;
                Formie::$plugin->getPayments()->savePayment($payment);
                $this->afterProcessPayment($submission, true);
                return PaymentDecision::succeeded($this->handle, $payment->reference);
            },
        );
    }
}
```

The example uses a provider without documented idempotent retries: `PaymentAttempt` records that the request was sent, so a lost response becomes `unknown` and cannot blindly issue another charge. For an API with a documented idempotency window, use the existing `DeliveryAttempt` resource claim with a stable key, bounded retry window and authenticated lookup. Never derive a fresh key just because the response timed out.

## Money And Outcomes

`Payment.amount` is a decimal string. `PaymentMoney::fromDecimal('25.01', 'USD')` exposes `minor === '2501'`, currency and `decimal()`. `fromMinor()` reverses the conversion. Excess nonzero fractional digits, scientific notation and invalid currencies are rejected. Integer-only provider APIs must use `integer()`, which checks overflow. Legacy numeric settings are normalized on entry; extension code should pass decimal strings and avoid float arithmetic. Browser amount previews do not authorize the server amount.

`PaymentDecision.status` is a `PaymentDecisionStatus` enum; `toArray()` serializes its string value. Use `succeeded()`, `requiresAction()` with an immutable `PaymentAction`, `pending()`, `failed()`, `cancelled()` or `unknown()`. Construct the complete action in one call with `PaymentAction::redirect()`, `confirm()`, `challenge()` or `initialize()`. Its `PaymentResumeMode` states what the browser does next: `RESUBMIT` sends the form again after an in-page provider action, `RETURN` sends the customer through a server-verified return URL, and `POLL` reads the stored payment status. A redirect is an action-required decision with a redirect action. Unknown outcomes stay incomplete and map to the submission's payment-pending outcome; cancellation maps to payment-failed while retaining `paymentStatus: cancelled`. Do not turn a timeout into a decline.

## Transactions And Replay

The workflow first commits the incomplete submission and durable payment attempt. Provider requests run outside database transactions. Successful provider evidence is retained separately while the published payment stays processing. The second transaction publishes the payment result, its submission-transition receipt and the submission completion decision together. If that transaction rolls back, authorized payment replay uses the saved evidence without charging again. Recurring invoice payments are separate records and cannot satisfy an initial submission requirement.

Replay uses the existing `PAYMENT_REPLAY` submission command, authority, version checks and operation receipts. It does not repopulate or revalidate the original form. It does verify the saved payment against the current amount and currency requirement. A successful charge may therefore remain associated with an incomplete submission that needs operator review.

## Endpoint Contracts

| Endpoint | Authority and behavior |
| --- | --- |
| `payment-webhooks/process-webhook` | Stable integration UID plus adapter-authenticated `PaymentWebhookCommand`; CSRF exempt; request body limited to 1 MiB; durable receipt before asynchronous handling or acknowledgement. |
| `payment-return/index` | `payment.return` capability resolved by `PaymentReturnCommand`; reconciles when due and redirects to the status page. Browser parameters cannot confirm payment. |
| `payment-status/status`, `payment-status/poll-status` | Read-only `payment.status` capability. Polling reconciles only when the stored cadence is due; the browser cannot force a provider request. Both endpoints are rate limited. |
| `payment-sessions/initialize` | `PaymentSessionCommand`; POST with CSRF and an expiring form/site/field/integration session capability. Currently used by Opayo. |
| `payment-challenges/complete` | Opayo's cross-origin POST; challenge-only payment capability. A sent challenge is not posted again after an uncertain response. |
| `payment-subscriptions/cancel` | `CancelSubscriptionCommand`; cancellation-only capability, GET confirmation, CSRF-protected POST and authority rechecked under the cancellation lock. |

## Webhook Evidence

Payment adapters authenticate and normalize provider input; Formie owns receipt storage, deduplication, locking, retries and diagnostics. Implement `verifyWebhook(PaymentWebhookCommand $request): VerifiedWebhookBatch` to verify the exact request bytes before returning one or more normalized `VerifiedWebhook` events. Give every event the provider's stable event ID. Use `getWebhookAccountFingerprint()` with the provider environment and account ID where one is available. Otherwise pass a one-way fingerprint derived from the account credential, never the raw secret, so reconnecting the integration to another account creates a separate event namespace. Include only headers needed as verification evidence.

Implement `handleWebhook(PaymentWebhookReceipt $receipt): void` for domain handling. It receives the stored normalized payload, not the public request. Confirm the provider resource belongs to the saved integration and financial record before updating it. Use Formie's payment and subscription services so ownership locks, immutable amount snapshots and monotonic terminal states remain enforced.

Invalid authentication or malformed payloads return non-2xx without a verified receipt. Once the complete verified batch is durable, Formie acknowledges the provider and processes each receipt through the queue. Duplicate receipts are acknowledged without rerunning completed side effects. A later handling failure remains internally owned: Formie schedules bounded delayed retries and records a safe diagnostic rather than asking the provider to repair an internal queue failure. Payment success and terminal subscription states cannot regress; provider subscription updates read current provider state rather than applying old snapshots.

Canonical bodies, normalized payloads and required headers are encrypted with the Formie security key. Keep that key and database backups together. The default support projection contains only bounded event identifiers and resource references plus an escaped redaction marker. Financial history and webhook receipts are retained indefinitely; normal cleanup does not remove them. Receipt histories retain the latest 100 transitions. Raw evidence is available only to a trusted console operator or a user with integration-management permission; it is excluded from the ordinary console receipt listing. See [Console Commands](../console-commands).

GoCardless deduplicates per event even when a retry batches it with other events; the first authenticated complete request body is retained as canonical evidence. Mollie uses a payment-specific secret plus an authenticated server lookup, and records each provider status observation. Keep provider secrets and capability URLs out of public logs.

## Subscriptions

Use `prepareSubscription()` before remote creation. `SubscriptionStatus` has pending, active, suspended, cancelling, cancelled, expired and unknown states. Preserve provider snapshots in `subscriptionData`; use `recordRecurring()` for invoice/charge history. Plans are optional. Cancellation records intent before the API call; a lost response stays unknown and must reconcile rather than repeat. `deleteSubscription()` archives the aggregate. Foreign keys retain payments when subscription, form content or provider configuration is physically removed; scope snapshots retain the former owner.


## Payment Field Template

Payment integrations render their field template from `integrations/payments/{handle}/field`. The exact markup depends on the provider, but most provider templates include a hidden base input for field errors, one or more hidden transport inputs, and a placeholder for the provider UI.

```twig
{{ fieldtag('fieldInput') }}

<input type="hidden" name="{{ field.getHtmlName() }}">
<input type="hidden" name="{{ field.getHtmlName('examplePaymentToken') }}">

<div data-example-payment-card></div>
```

The hidden input suffix should match the value written by the browser module and the value read in `executePayment()`.

## Browser Modules

Payment provider modules should usually use `definePaymentModule()` from `@verbb/formie-browser`. It gives you shared services for updating hidden payment inputs, showing payment errors, resolving dynamic amounts and currencies, and participating in Formie’s browser payment stage.

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
  moduleId: 'example:example-payment',
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

  onBeforePayment: async ({ widget, services }) => {
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

`onBeforePayment()` runs before Formie dispatches the submission. Use it when the provider needs to tokenize card details, confirm a wallet payment or produce an id that the server must receive. Return `false` to stop submission and keep the user on the form.

## Methods

Payment integrations commonly use these methods:

Method | Use
--- | ---
`renderFieldHtml()` | Renders the provider’s Payment field template.
`getBrowserModule()` | Registers browser behaviour for the Payment field.
`executePayment()` | Provider implementation called by the inherited locked, durable `processPayment()` boundary.
`getAmount()` | Resolves the configured fixed or dynamic amount from the Payment field.
`getCurrency()` | Resolves the configured fixed or dynamic currency from the Payment field.
`getPaymentFieldPayload()` | Reads provider-specific hidden input values from the submitted Payment field.
`addFieldError()` | Adds an error to the Payment field when processing fails.
`supportsWebhooks()` | Whether the provider can send webhook updates.
`verifyWebhook()` | Authenticates exact request evidence and returns normalized provider events without changing payment state.
`handleWebhook()` | Applies one durable normalized receipt under Formie's processing lock.
`getTransaction()` | Authenticated provider reconciliation; never trusts browser return fields.
`requiresAjaxSubmission()` | Whether forms using this provider must submit with Ajax.
`getWebhookUrl()` | Returns the stable integration-UID webhook URL Formie exposes for the payment provider.
