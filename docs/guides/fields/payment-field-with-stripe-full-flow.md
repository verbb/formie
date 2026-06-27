# Payment field with Stripe — full flow

This walkthrough connects Stripe to a Formie form end to end: integration setup, Payment field configuration, form behaviour, and optional subscription features.

## Prerequisites

- A Stripe account with API keys
- A Formie form that will collect payment
- **Ajax (Client-side)** submission method on the form (required for Stripe)

## Overview

Stripe setup happens in two places:

1. **Formie → Settings → Payments** — create and connect the Stripe integration.
2. **Form builder** — add a Payment field and choose Stripe as the provider.

Payment runs through Formie's submission workflow: the submission is validated and screened, payment is authorised or captured during the **save** stage, and notifications and integrations dispatch only after payment succeeds (for charge-at-submit flows).

## Step 1: Create the Stripe integration

1. Go to **Formie → Settings → Payments**.
2. Click **New Integration**.
3. Select **Stripe** as the provider.
4. In the [Stripe Dashboard → API Keys](https://dashboard.stripe.com/apikeys), copy the **Publishable Key** and **Secret Key**.
5. Paste them into Formie. Store keys in `.env` variables and reference them in the integration settings — do not commit secrets to project config.
6. Save and click **Refresh** in the sidebar to test the connection.

Use Stripe **Test Mode** keys while developing; switch to live keys before production.

## Step 2: Configure webhooks (subscriptions)

Webhooks are required for subscription payments and recommended for reliable status updates.

1. In Stripe, open **Developers → Webhooks**.
2. Click **Create an event destination**.
3. Copy the **Redirect URI** from Formie integration settings into Stripe's **Endpoint URL**.
4. Select events — at minimum:
   - `customer.subscription.created`
   - `customer.subscription.deleted`
   - `customer.subscription.updated`
   - `invoice.created`
   - `invoice.payment_failed`
   - `invoice.payment_succeeded`
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `payment_intent.canceled`
   - `plan.deleted`
   - `plan.updated`
5. Copy the **Signing secret** into Formie's **Webhook Signing Secret** field.

## Step 3: Add a Payment field

1. Edit your form.
2. Set **Submission Method** to **Ajax (Client-side)**. Stripe tokenisation requires Formie's client submission flow.
3. Add a **Payment** field.
4. Select your Stripe integration for **Payment Integration**.
5. Configure **Amount** — fixed value, dynamic value from another field, or use [Calculations](/fields/calculations) to derive the amount.
6. Map **Billing details** — name, email, address fields — to Stripe's expected customer data.

Save the form and preview on the front end. Stripe Elements (or the configured hosted UI) should render inside the Payment field.

## Step 4: Submit and verify

1. Submit a test payment using Stripe test card `4242 4242 4242 4242`.
2. Confirm the submission appears in **Formie → Submissions** with payment metadata.
3. Check that email notifications and integrations fired after successful payment.

Failed payments should surface as submission errors on the form without creating a completed paid submission.

## Subscriptions

For recurring payments:

1. Edit the Payment field and set **Payment Type** to **Subscription**.
2. Configure the recurring amount and interval per Stripe integration settings.

### Payment limits

Limit how many billing cycles Stripe collects before auto-cancelling:

1. Under **Payment Limit**, choose **Fixed Value** or **Dynamic Value**.
2. Enter a count (for example `3` for three instalments) or select a Number field.

Formie creates the subscription through a Stripe subscription schedule. Leave **No limit** for ongoing subscriptions.

### Setup fees

Charge a one-time fee on the first invoice:

1. Under **Setup Fee**, choose **Fixed Value** or **Dynamic Value**.
2. Optionally set **Setup Fee Description** for the Stripe invoice line item.

Setup fees use Stripe `add_invoice_items` on the first subscription invoice.

Modify subscription payloads with the `modifySubscriptionPayload` and `modifySubscriptionSchedulePayload` events if your project needs custom Stripe parameters.

## Dynamic amounts with Calculations

When the payment amount depends on other fields:

1. Add Number or Dropdown fields for the inputs.
2. Add a **Calculations** field for the total.
3. On the Payment field, set amount source to the Calculations field (dynamic value).

The Calculations field updates on the front end as inputs change; the Payment field reads the computed value at submit time.

## Troubleshooting

| Symptom | Check |
| --- | --- |
| Stripe UI does not appear | Form must use Ajax submission; confirm publishable key and front-end assets load |
| Payment succeeds but no submission | Webhook/callback configuration; check Craft logs for payment replay errors |
| Integration refresh fails | Secret key, API mode (test vs live), server outbound HTTPS |
| Subscriptions stuck pending | Webhook signing secret and required subscription events |

## Related

- [Payment field](/fields/payment)
- [Stripe integration](/integrations/payments/stripe)
- [Calculations field in detail](/guides/fields/calculations-field-in-detail)
- [Submission workflow and stages explained](/guides/submissions-workflows/submission-workflow-and-stages-explained)
