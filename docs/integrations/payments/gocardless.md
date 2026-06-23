# GoCardless

Follow the below steps to connect to the GoCardless API.

Formie uses GoCardless **Billing Request Flows** for hosted Direct Debit authorisation. After the customer authorises their bank details, Formie creates the one-off payment against the mandate and tracks status through webhooks and the status page.

> Direct Debit payments are asynchronous. Customers are redirected back to your site while GoCardless collects the payment over the next few business days. Formie finalises the submission when GoCardless confirms the payment.

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
3. Enable payment and billing request events in GoCardless.

Formie listens for payment status updates and billing request fulfilment events. Do not rely on the customer redirect alone to confirm payment status.

### Step 4. Test Connection

1. Save this integration.
2. Click on the **Refresh** button in the right-hand sidebar.

### Step 5. Field Settings

1. Go to the form you want to enable this integration on.
2. Add a **Payment** field to your form.
3. Select GoCardless for the **Payment Provider**.
4. Configure the payment **Currency** and **Amount**.
5. Optionally map **Billing Details** so Formie can prefill customer details on the GoCardless hosted page.

## How It Works

1. The customer submits your form.
2. Formie creates a GoCardless **Billing Request** and **Billing Request Flow**.
3. The customer is redirected to GoCardless to authorise Direct Debit.
4. When they return, Formie creates the payment against the mandate and shows the status page.
5. GoCardless webhooks update the payment status as collection progresses.

## Limitations

- **One-off Direct Debit payments only**.
- **Scheme selection** is inferred from the payment currency (for example GBP → BACS, EUR → SEPA).
- **Instant Bank Pay** and combined instant + Direct Debit flows are not supported yet.

## Troubleshooting

**Pending billing request but no payment amount in GoCardless** — Formie creates the payment after the customer completes the hosted authorisation flow. Check that webhooks are configured and that the customer returned to the Formie status page.

**Payment stays pending** — This is normal for Direct Debit until GoCardless confirms collection. Use webhooks or wait for the status page polling to refresh.

**Sandbox testing** — Enable **Use Sandbox** on the integration and use sandbox API credentials.
