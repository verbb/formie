# PayPal
Connect PayPal to take payments through the provider configured for your form. You need access to configure the form in Craft and credentials for the destination account. The steps below establish the connection; finish by sending a test submission to verify the result.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Payments**.
1. Click the **New Integration** button.
1. Select **PayPal** as the **Integration Provider**.

### Step 2. Connect to the PayPal API
1. Go to your <a href="https://developer.paypal.com/developer/applications/" target="_blank">PayPal REST API</a> application settings.
1. Select either **Sandbox** or **Live** and click the **Create App** button.
1. Enter a **App Name** and select **Merchant** for the **App Type**.
1. Copy the **Client ID** from PayPal and paste in the **Client ID** field in Formie.
1. Copy the **Secret** from PayPal and paste in the **Client Secret** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Field Setting
1. Go to the form you want to enable this integration on.
1. Add a **Payment** field to your form.
1. Select PayPal for the **Payment Provider**.

The PayPal payment integration supports only once-off payments.

## Verify a Submission

Save the form, open it on your site and submit recognisable test values. Complete a test payment and inspect both the saved submission and the provider’s transaction record. Confirm the amount, currency and final payment state before enabling live payments.

If nothing arrives, check whether integration conditions matched, whether the submission was complete and non-spam, and whether Craft’s queue has processed the job. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Connect and Test an Integration](/integrations/connect-and-test-an-integration) for a complete mapping and verification workflow.
