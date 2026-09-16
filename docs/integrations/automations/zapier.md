# Zapier
Connect Zapier to pass submitted answers to an automated workflow. You need access to configure the form in Craft and credentials for the destination account. The steps below establish the connection; finish by sending a test submission to verify the result.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Automations**.
1. Click the **New Integration** button.
1. Select **Zapier** as the **Integration Provider**.

### Step 2. Connect to the Zapier API
1. Go to <a href="https://zapier.com/app/zaps" target="_blank">Zapier</a> and create a new Zap.
1. For the **Choose App & Event** value, enter **Webhooks by Zapier**.
1. For the **Choose Trigger Event** value, enter **Catch Hook**.
1. Click **Continue**.
1. Copy the **Custom Webhook URL** into the **Webhook** field in Formie.

### Step 3. Form Setting & Test Payload

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
