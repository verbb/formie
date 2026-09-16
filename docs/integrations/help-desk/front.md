# Front
Connect Front to send support enquiries to your help-desk account. You need access to configure the form in Craft and credentials for the destination account. The steps below establish the connection; finish by sending a test submission to verify the result.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Help Desk**.
1. Click the **New Integration** button.
1. Select **Front** as the **Integration Provider**.

### Step 2. Connect to the Front API
1. Go to <a href="https://app.frontapp.com/settings/developers" target="_blank">Front Developer Portal</a> and log into your account.
1. Click **Create App**, and enter a name for your app (e.g. `Formie`).
1. Click **OAuth** tab, and the **Enable OAuth** button.
1. In the **Redirect URLs** field, enter the value from the **Redirect URI** field in Formie.
1. Copy the **Client ID** from Front and paste in the **Client ID** field in Formie.
1. Copy the **Client Secret** from Front and paste in the **Client Secret** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.

### Step 4. Form Settings

1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Choose the destination options offered by this integration.
1. Map required fields with the variable picker. For a support enquiry, map the visitor’s email and message where those fields are available.
1. Enable the integration.
1. Click **Save** to save the form.

## Verify a Submission

Save the form, open it on your site and submit recognisable test values. Find the resulting enquiry in the selected account and check the sender details and submitted message.

If nothing arrives, check whether integration conditions matched, whether the submission was complete and non-spam, and whether Craft’s queue has processed the job. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Connect and Test an Integration](/integrations/connect-and-test-an-integration) for a complete mapping and verification workflow.
