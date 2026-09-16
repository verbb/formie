# Moneris
Connect Moneris to take payments through the provider configured for your form. You need access to configure the form in Craft and credentials for the destination account. The steps below establish the connection; finish by sending a test submission to verify the result.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Payments**.
1. Click the **New Integration** button.
1. Select **Moneris** as the **Integration Provider**.

### Step 2. Connect to the Moneris API
1. Go to the <a href="https://www3.moneris.com/mpg" target="_blank">Moneris Dashboard</a>.
1. Navigate to **Admin** → **Store Settings**.
1. Copy the **Store ID** from Moneris and paste in the **Store ID** field in Formie.
1. Copy the **API Token** from Moneris and paste in the **API Token** field in Formie.
1. Navigate to **Admin** → **Hosted Tokenization**.
1. Enter the domain for your site.
1. Click the **Create Profile** button.
1. Copy the **Profile ID** from Moneris and paste in the **Profile ID** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Field Setting
1. Go to the form you want to enable this integration on.
1. Add a **Payment** field to your form.
1. Select Moneris for the **Payment Provider**.

The Moneris payment integration supports only once-off payments.

## Verify a Submission

Save the form, open it on your site and submit recognisable test values. Complete a test payment and inspect both the saved submission and the provider’s transaction record. Confirm the amount, currency and final payment state before enabling live payments.

If nothing arrives, check whether integration conditions matched, whether the submission was complete and non-spam, and whether Craft’s queue has processed the job. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Connect and Test an Integration](/integrations/connect-and-test-an-integration) for a complete mapping and verification workflow.
