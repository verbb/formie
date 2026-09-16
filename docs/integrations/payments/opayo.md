# Opayo
Connect Opayo to take payments through the provider configured for your form. You need access to configure the form in Craft and credentials for the destination account. The steps below establish the connection; finish by sending a test submission to verify the result.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Payments**.
1. Click the **New Integration** button.
1. Select **Opayo** as the **Integration Provider**.

### Step 2. Connect to the Opayo API
1. Go to the <a href="https://test.sagepay.com/mysagepay/settings.msp" target="_blank">Opayo Dashboard</a>.
1. Navigate to **Settings** → **Administrator**.
1. Click the **Create API Credentials** button.
1. Copy the **Integration Key** from Opayo and paste in the **Integration Key** field in Formie.
1. Copy the **Integration Password** from Opayo and paste in the **Integration Password** field in Formie.
1. Enter your **Vendor Name**  in the **Vendor Name** field in Formie.

### Step 3. Choose a Checkout Mode
1. Select **Checkout Mode** for the integration:
   - **Own Form** — card fields are rendered on your page and tokenised client-side. This is the default.
   - **Drop-in Checkout** — card fields are rendered inside an Opayo-hosted iframe on your page, which can reduce PCI scope compared to Own Form.

### Step 4. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 5. Field Setting
1. Go to the form you want to enable this integration on.
1. Add a **Payment** field to your form.
1. Select Opayo for the **Payment Provider**.

The Opayo payment integration supports only once-off payments.

## Verify a Submission

Save the form, open it on your site and submit recognisable test values. Complete a test payment and inspect both the saved submission and the provider’s transaction record. Confirm the amount, currency and final payment state before enabling live payments.

If a 3D Secure callback cannot confirm payment, Formie leaves the payment pending. Check its transaction in Opayo before asking the visitor to pay again. An “operation not allowed” response can have more than one cause and does not by itself confirm a successful payment.

If nothing arrives, check whether integration conditions matched, whether the submission was complete and non-spam, and whether Craft’s queue has processed the job. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Connect and Test an Integration](/integrations/connect-and-test-an-integration) for a complete mapping and verification workflow.
