# Twilio
Connect Twilio to send a message when a visitor completes your form. You need access to configure the form in Craft and credentials for the destination account. The steps below establish the connection; finish by sending a test submission to verify the result.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Messaging**.
1. Click the **New Integration** button.
1. Select **Twilio** as the **Integration Provider**.

### Step 2. Connect to the Twilio API
1. Go to the <a href="https://www.twilio.com/console" target="_blank">Twilio Console</a> and log in.
1. Copy the **Account SID** from Twilio and paste in the **Account SID** field in Formie.
1. Copy the **Auth Token** from Twilio and paste in the **Auth Token** field in Formie.
1. Navigate to **Phone Numbers** → **Manage** → **Active Numbers**.
1. Select a Twilio phone number you own.
1. Paste this into the **From Number** field in Formie.

### Step 3. Test the Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar to verify the connection.

### Step 4. Form Settings

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
