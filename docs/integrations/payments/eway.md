# Eway
Connect Eway to take payments through the provider configured for your form. You need access to configure the form in Craft and credentials for the destination account. The steps below establish the connection; finish by sending a test submission to verify the result.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Payments**.
1. Click the **New Integration** button.
1. Select **Eway** as the **Integration Provider**.

### Step 2. Connect to the Eway API
1. Log in to the <a href="https://my.eway.io/" target="_blank">Eway Partner Portal</a>.
1. Navigate to **My Account** → **API Key**.
1. Copy the **API Key** from Eway and paste in the **API Key** field in Formie.
1. Copy the **Password** from Eway and paste in the **API Password** field in Formie.
1. Copy the **Client Side Encryption Key** from Eway and paste in the **Client Side Encryption Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Field Setting
1. Go to the form you want to enable this integration on.
1. Add a **Payment** field to your form.
1. Select Eway for the **Payment Provider**.

## Verify a Submission

Save the form, open it on your site and submit recognisable test values. Complete a test payment and inspect both the saved submission and the provider’s transaction record. Confirm the amount, currency and final payment state before enabling live payments.

If nothing arrives, check whether integration conditions matched, whether the submission was complete and non-spam, and whether Craft’s queue has processed the job. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Connect and Test an Integration](/integrations/connect-and-test-an-integration) for a complete mapping and verification workflow.
