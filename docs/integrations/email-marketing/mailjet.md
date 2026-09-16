# Mailjet
Connect Mailjet to send newsletter sign-ups to your chosen mailing list. You need access to configure the form in Craft and credentials for the destination account. The steps below establish the connection; finish by sending a test submission to verify the result.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select Mailjet as the **Integration Provider**.

### Step 2. Connect to the Mailjet API
1. Go to <a href="https://app.mailjet.com/" target="_blank">Mailjet</a> and login to your account.
1. Open **Account settings** → **API Key Management (Primary and Subaccounts)**.
1. Copy the **API Key** from Mailjet and paste it into the **API Key** field in Formie.
1. If you already have the matching **Secret Key**, copy it into the **Secret Key** field in Formie.
1. If you do not have the **Secret Key**, click the cogwheel next to the API Key in Mailjet and select **Reset Secret Key**.
1. Confirm the reset in Mailjet, then copy the new **Secret Key** immediately and paste it into the **Secret Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

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

### Additional Features
- Supports mapping to Mailjet contact metadata fields.

## Verify a Submission

Save the form, open it on your site and submit recognisable test values. Look up the test email in the selected list and check its mapped values and subscription state. If the provider requires confirmation, complete that step before expecting an active subscription.

If nothing arrives, check whether integration conditions matched, whether the submission was complete and non-spam, and whether Craft’s queue has processed the job. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Connect and Test an Integration](/integrations/connect-and-test-an-integration) for a complete mapping and verification workflow.
