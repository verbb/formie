# GoCardless

Follow the below steps to connect to the GoCardless API.

Formie uses GoCardless **Billing Request Flows** for hosted Direct Debit authorisation. After the customer authorises their bank details, Formie creates a one-off payment or recurring subscription against the mandate and tracks status through webhooks and the status page.

> Direct Debit payments are asynchronous. Customers are redirected back to your site while GoCardless collects the payment over the next few business days. One-off submissions finalise when GoCardless confirms the payment. Subscription submissions finalise once the GoCardless subscription is active.

## Setup

### Step 1. Create the Integration

1. Navigate to **Formie** → **Settings** → **Payments**.
2. Click the **New Integration** button.
3. Select **GoCardless** as the **Integration Provider**.

### Step 2. Connect to the GoCardless API

1. Go to the <a href="https://manage.gocardless.com/" target="_blank">GoCardless Dashboard</a> (or use the <a href="https://manage-sandbox.gocardless.com/" target="_blank">sandbox environment</a> for testing).
2. Navigate to **Developers** → **Developers**.
3. Click the **Create Access Token** button.
4. Copy the **Access Token** from GoCardless and paste it in the **Access Token** field in Formie.

### Step 3. Configure Webhooks

1. In the GoCardless dashboard, create a webhook endpoint pointing to your Formie webhook URL:

```text
https://your-site.test/formie/payment-webhooks/process-webhook?handle=yourGoCardlessHandle
```

2. Copy the webhook signing secret into Formie’s **Webhook Secret Key** field.
3. Enable payment, billing request, and subscription events in GoCardless.

Formie listens for payment status updates, billing request fulfilment, and subscription lifecycle events. Do not rely on the customer redirect alone to confirm payment status.

### Step 4. Test Connection

1. Save this integration.
2. Click on the **Refresh** button in the right-hand sidebar.

### Step 5. Field Settings

1. Go to the form you want to enable this integration on.
2. Add a **Payment** field to your form.
3. Select GoCardless for the **Payment Provider**.
4. Choose **Once-off** or **Subscription** for the **Payment Type**.
5. Configure the payment **Currency** and **Amount**.
6. For subscriptions, configure the billing frequency and subscription description.
7. Optionally map **Billing Details** so Formie can prefill customer details on the GoCardless hosted page.

## How It Works

### Once-off payments

1. The customer submits your form.
2. Formie creates a GoCardless **Billing Request** and **Billing Request Flow**.
3. The customer is redirected to GoCardless to authorise Direct Debit.
4. When they return, Formie creates the payment against the mandate and shows the status page.
5. GoCardless webhooks update the payment status as collection progresses.

### Subscriptions

1. The customer submits your form.
2. Formie creates a GoCardless **Billing Request** and **Billing Request Flow** for mandate authorisation.
3. The customer is redirected to GoCardless to authorise Direct Debit.
4. When they return, Formie creates a GoCardless **Subscription** against the mandate.
5. GoCardless collects recurring payments automatically. Formie tracks subscription status and upcoming payment dates via webhooks.

Customers can cancel subscriptions using Formie’s subscription cancel URL when included in notifications.

## Limitations

- **Direct Debit only** — Instant Bank Pay and combined instant + Direct Debit flows are not supported yet.
- **Scheme selection** is inferred from the payment currency (for example GBP → BACS, EUR → SEPA).
- **Subscription intervals** support weekly, monthly, and yearly billing only.
- **Setup fees, payment limits, and trials** are not supported for GoCardless subscriptions.

## Troubleshooting

**Pending billing request but no payment amount in GoCardless** — Formie creates the payment or subscription after the customer completes the hosted authorisation flow. Check that webhooks are configured and that the customer returned to the Formie status page.

**Payment stays pending** — This is normal for Direct Debit until GoCardless confirms collection. Use webhooks or wait for the status page polling to refresh.

**Subscription active but submission still incomplete** — Ensure webhooks are configured and that the status page can refresh the subscription state. The submission finalises once the GoCardless subscription is active.

**Sandbox testing** — Enable **Use Sandbox** on the integration and use sandbox API credentials.
