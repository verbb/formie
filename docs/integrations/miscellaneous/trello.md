# Trello
Connect Trello to send submitted information to your connected account. You need access to configure the form in Craft and credentials for the destination account. The steps below establish the connection; finish by sending a test submission to verify the result.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Miscellaneous**.
1. Click the **New Integration** button.
1. Select Trello as the **Integration Provider**.

### Step 2. Connect to the Trello API
1. Go to the <a href="https://trello.com/app-key" target="_blank">Trello API Key</a> page.
1. Under the **Developer API Keys** heading, copy the **Key** value into the **Client ID** field in Formie.
1. Under the **Allowed Origins** heading, enter the value from the **Redirect URI** field in Formie into the text field under **New Allowed Origin** and hit **Submit**.
1. Under the **OAuth** heading, copy the **Secret** value into the **Client Secret** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to Trello, where you must approve Formie to access your Trello account.

### Step 4. Form Setting

1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Choose the destination supported by this integration.
1. Use the variable picker to map its required fields to the appropriate form answers.
1. Enable the integration.
1. Click **Save** to save the form.

## Verify a Submission

Save the form, open it on your site and submit recognisable test values. Find the resulting item in the destination you selected and compare its values with the test submission.

If nothing arrives, check whether integration conditions matched, whether the submission was complete and non-spam, and whether Craft’s queue has processed the job. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Connect and Test an Integration](/integrations/connect-and-test-an-integration) for a complete mapping and verification workflow.
