# Stripe (Headless)

This page covers Stripe payments in **headless / GraphQL** front-ends where you bring your own Stripe.js UI. For Twig or browser-managed forms, see [Stripe](/integrations/payments/stripe).

## Overview

1. Load form config and session with `formieClientForm`.
2. Initialize Stripe.js with the `publishableKey` from the payment field module config.
3. Create or confirm a PaymentIntent (or SetupIntent for subscriptions) using Stripe’s APIs.
4. Submit to Formie via `submitFormieClientForm` with `stripePaymentIntentId`.
5. If Formie returns `paymentStatus: actionRequired`, run Stripe’s confirm step and **resubmit with the same session**.

Formie never replaces Stripe.js — it orchestrates server-side charging, stores the submission, and tells your app when follow-up is required.

## 1. Read payment config

Query the client form definition:

```graphql
query FormieClientForm($handle: String!) {
    formieClientForm(handle: $handle) {
        definition
        session
    }
}
```

Find the Payment field module in `definition.fields` (or nested pages). The Stripe module config includes:

- `publishableKey` — pass to `loadStripe()`
- `paymentType` — `single` or `subscription`
- `amountType` / `currencyType` — fixed or dynamic; use `initialPaymentInformation` as a starting amount
- `requiredInputSuffixes` — `["stripePaymentIntentId"]`
- `billingDetails` — field references for pre-filling Stripe billing details

## 2. Collect payment with Stripe.js

Use [Stripe.js](https://stripe.com/docs/js) directly in your front-end. A minimal once-off flow:

```javascript
import { loadStripe } from '@stripe/stripe-js';

const stripe = await loadStripe(publishableKey);
const elements = stripe.elements({ clientSecret });
const paymentElement = elements.create('payment');
paymentElement.mount('#payment-element');

// After the customer submits your form UI:
const { error, paymentIntent } = await stripe.confirmPayment({
    elements,
    redirect: 'if_required',
});

if (error) {
    // Show error in your UI
    return;
}
```

Create the PaymentIntent client-side or server-side in your own API if you prefer — Formie only needs the intent ID (and optional payment method ID) on submit.

## 3. Submit to Formie

```graphql
mutation SubmitFormieClientForm($input: FormieClientSubmitInput!) {
    submitFormieClientForm(input: $input) {
        success
        submissionUid
        paymentStatus
        paymentMessage
        paymentAction
        paymentDecision
        keepSubmitLoading
        session {
            id
            continuation
            tokens
        }
        messages
        errors
    }
}
```

Example variables (use the Payment field’s builder ID as the value key):

```json
{
    "input": {
        "handle": "checkoutForm",
        "action": "submit",
        "session": {
            "id": "formie-checkoutForm-abc123",
            "currentPageId": "page-final",
            "tokens": { "...": "..." }
        },
        "values": {
            "field-payment-1": {
                "stripePaymentIntentId": "pi_3Example",
                "stripePaymentId": "pm_1Example"
            }
        }
    }
}
```

## 4. Handle 3DS / action required

When additional authentication is needed, Formie responds with:

```json
{
    "success": false,
    "paymentStatus": "actionRequired",
    "keepSubmitLoading": true,
    "paymentMessage": "Additional payment confirmation is required to continue.",
    "paymentAction": {
        "type": "confirm",
        "provider": "stripe",
        "payload": {
            "clientSecret": "pi_3Example_secret_..."
        }
    }
}
```

In your app:

1. Show `paymentMessage` as a neutral notice (not a form validation error).
2. Call `stripe.confirmPayment()` with the `clientSecret` from `paymentAction.payload`.
3. Resubmit **the same form session** with the same (or updated) `stripePaymentIntentId`.

Formie uses `paymentReplay` internally so the second submit continues the original submission instead of creating a duplicate.

## 5. Subscriptions

For subscription payment fields, also pass `stripeSubscriptionId` when available. Formie uses it to link the Craft submission to the Stripe subscription record.

Subscription setup fees and payment limits configured in the form builder are applied server-side — no extra GraphQL fields are required.

## Typed mutation input

When using form-specific mutations, Stripe payment fields expose a typed input:

```graphql
mutation SaveCheckout($payment: checkoutForm_payment_FormiePaymentInput) {
    save_checkoutForm_Submission(payment: $payment) {
        id
    }
}
```

Prefer `submitFormieClientForm` for headless apps so session and payment follow-up metadata stay in one response contract.

## Troubleshooting

**Duplicate submissions after 3DS** — Always pass the `session` object from the previous `submitFormieClientForm` response. Do not start a fresh `formieClientForm` query between follow-up submits unless you intend to abandon the in-progress submission.

**Generic form error on 3DS** — Check `paymentStatus` and `paymentMessage`. Action-required responses clear form-level validation errors by design.

**Amount mismatch** — Dynamic amounts must match what Stripe expects. Use field values Formie can read when creating the intent, or align your Stripe amount with the form’s configured amount/currency settings.

## See also

- [Headless Payments](/graphql/payments) — general BYO payment guide
- [Stripe integration](/integrations/payments/stripe) — CP setup, webhooks, subscriptions
