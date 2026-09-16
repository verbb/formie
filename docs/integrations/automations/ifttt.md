# IFTTT
Connect IFTTT to pass submitted answers to an automated workflow. You need access to configure the form in Craft and credentials for the destination account. The steps below establish the connection; finish by sending a test submission to verify the result.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Automations**.
1. Click the **New Integration** button.
1. Select **IFTTT** as the **Integration Provider**.

### Step 2. Connect to the IFTTT API
1. Go to the <a href="https://ifttt.com/create" target="_blank">IFTTT Create page</a>.
1. Click **If This → Add**, then search for and select **Webhooks**.
1. Choose **Receive a web request** and enter an **Event Name** (e.g. `form_submission`). Make a note of **Event Name** for later.
1. Click **Then That → Add**, and choose an action like sending an email, Slack message, Google Sheets, etc.
1. Complete the applet and click **Finish**.

### Step 3. Get Your Webhook Key
1. Visit <a href="https://ifttt.com/maker_webhooks" target="_blank">https://ifttt.com/maker_webhooks</a>.
1. Click **Documentation**.
1. Copy your **Webhook Key** from the URL shown (`https://maker.ifttt.com/use/XXXXX`).
1. Paste it into the **Webhook Key** field in Formie.
1. Enter the same **Event Name** you used in IFTTT.

### Step 4. Form Settings

1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Choose the destination workflow or URL supported by this integration. Use a test destination while checking the connection.
1. Map the submitted values the workflow needs.
1. Enable the integration.
1. Click **Save** to save the form.
1. Click on the **Send Test Payload** button to send dummy content to the URL.

## Verify a Submission

Save the form, open it on your site and submit recognisable test values. Inspect the receiving workflow’s run history or request log. Check both the received values and the outcome of any following steps.

If nothing arrives, check whether integration conditions matched, whether the submission was complete and non-spam, and whether Craft’s queue has processed the job. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Connect and Test an Integration](/integrations/connect-and-test-an-integration) for a complete mapping and verification workflow.
