# Drip
Connect Drip to send newsletter sign-ups to your chosen mailing list. You need access to configure the form in Craft and credentials for the destination account. The steps below establish the connection; finish by sending a test submission to verify the result.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select Drip as the **Integration Provider**.

### Step 2. Connect to the Drip API
1. Go to <a href="https://www.getdrip.com/user/applications" target="_blank">Drip</a> and login to your account.
1. Click on **OAuth Applications**.
1. Enter a name for your application, and click the **Create Application** button.
1. In the **Callback URL** field, enter the value from the **Redirect URI** field In Formie.
1. Copy the **Client ID** from Drip and paste in the **Client ID** field in Formie.
1. Copy the **Client Secret** from Drip and paste in the **Client Secret** field in Formie.
1. Click the **Activate** button.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to Drip, where you must approve Formie to access your Drip account.

### Step 4. Form Setting

1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Select the destination list.
1. Map its required email field to your form’s **Email Address** field.
1. Map any additional fields your list requires using the variable picker.
1. If you collect a newsletter opt-in, configure integration conditions so only visitors who choose it are sent.
1. Enable the integration.
1. Click **Save** to save the form.

## Verify a Submission

Save the form, open it on your site and submit recognisable test values. Look up the test email in the selected list and check its mapped values and subscription state. If the provider requires confirmation, complete that step before expecting an active subscription.

If nothing arrives, check whether integration conditions matched, whether the submission was complete and non-spam, and whether Craft’s queue has processed the job. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Connect and Test an Integration](/integrations/connect-and-test-an-integration) for a complete mapping and verification workflow.
