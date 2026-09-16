# Paddle
Connect Paddle to take payments through the provider configured for your form. You need access to configure the form in Craft and credentials for the destination account. The steps below establish the connection; finish by sending a test submission to verify the result.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Payments**.
1. Click the **New Integration** button.
1. Select **Paddle** as the **Integration Provider**.

### Step 2. Connect to the Paddle API
1. Go to the <a href="https://vendors.paddle.com" target="_blank">Paddle Dashboard</a>.
1. Navigate to **Developer Tools** → **Authentication**.
1. Click the **New API Key** button.
1. Copy the **API Key** from Paddle and paste in the **API Key** field in Formie.
1. Navigate to **Client-side tokens**.
1. Click the **New Client-side Token** button.
1. Copy the **Token** from Paddle and paste in the **Client Side Token** field in Formie.

The API key needs permission to create products, prices and transactions, and to read transactions. Formie creates the transaction before opening checkout and verifies its final status, currency and amount with Paddle before completing the submission. Configure an approved default payment link in Paddle's checkout settings so its API can create checkout transactions.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Field Setting
1. Go to the form you want to enable this integration on.
1. Add a **Payment** field to your form.
1. Select Paddle for the **Payment Provider**.

The Paddle payment integration supports only once-off payments.

Keep the configured amount and currency unchanged while a checkout is in progress. If you change them, check the original transaction in Paddle before starting another payment. A completed payment must cover the configured amount; discounts that reduce the collected total below that amount cannot complete the submission.

## Verify a Submission

Save the form, open it on your site and submit recognisable test values. Complete a test payment and inspect both the saved submission and the provider’s transaction record. Confirm the amount, currency and final payment state before enabling live payments.

If nothing arrives, check whether integration conditions matched, whether the submission was complete and non-spam, and whether Craft’s queue has processed the job. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Connect and Test an Integration](/integrations/connect-and-test-an-integration) for a complete mapping and verification workflow.
