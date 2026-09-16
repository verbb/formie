# Slack
Connect Slack to send a message when a visitor completes your form. You need access to configure the form in Craft and credentials for the destination account. The steps below establish the connection; finish by sending a test submission to verify the result.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Messaging**.
1. Click the **New Integration** button.
1. Select **Slack** as the **Integration Provider**.

### Step 2. Connect to the Slack API
1. Go to the <a href="https://api.slack.com/apps?new_app=1" target="_blank">Slack App Center</a>.
1. Create a new app, by entering an **App Name** and **Development Slack Workspace**.
1. In the left-hand sidebar, under **Settings**, click **Basic Information**.
1. Under the **App Credentials** section, copy the **Client ID** and **Client Secret** values and paste into the fields in Formie.
1. In the left-hand sidebar, under **Features**, click **OAuth & Permissions**.
1. In the section **Redirect URLs**, click the **Add New Redirect URL** button and enter the value from the **Redirect URI** field in Formie.
1. Then click the **Add** button, then click the **Save URLs** button.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to Slack, where you must approve Formie to access your Slack account.

### Step 4. Form Setting

1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Set the recipient or channel supported by the integration.
1. Use the variable picker to include the submitted values your team needs in the message.
1. Enable the integration.
1. Click **Save** to save the form.

## Verify a Submission

Save the form, open it on your site and submit recognisable test values. Check the intended recipient or channel for the test message and verify that its values match the submission.

If nothing arrives, check whether integration conditions matched, whether the submission was complete and non-spam, and whether Craft’s queue has processed the job. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Connect and Test an Integration](/integrations/connect-and-test-an-integration) for a complete mapping and verification workflow.
