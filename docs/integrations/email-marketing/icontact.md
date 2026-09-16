# iContact
Connect iContact to send newsletter sign-ups to your chosen mailing list. You need access to configure the form in Craft and credentials for the destination account. The steps below establish the connection; finish by sending a test submission to verify the result.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select iContact as the **Integration Provider**.

### Step 2. Connect to the iContact API
1. Go to <a href="https://www.icontact.com/" target="_blank">iContact</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Settings & Billing**.
1. Click on **iContact Integrations**.
1. Find the **Custom API Integrations** item and click the **Create** button.
1. Copy the **Application ID (AppId)** from iContact and paste in the **Application ID** field in Formie.
1. Copy the **Username / Email Address** from iContact and paste in the **Username** field in Formie.
1. Copy the **Password** from iContact and paste in the **Password** field in Formie.
1. Copy the **Account ID** from iContact and paste in the **Account ID** field in Formie.
1. Copy the **Client Folder ID** from iContact and paste in the **Client Folder ID** field in Formie.

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

## Verify a Submission

Save the form, open it on your site and submit recognisable test values. Look up the test email in the selected list and check its mapped values and subscription state. If the provider requires confirmation, complete that step before expecting an active subscription.

If nothing arrives, check whether integration conditions matched, whether the submission was complete and non-spam, and whether Craft’s queue has processed the job. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Connect and Test an Integration](/integrations/connect-and-test-an-integration) for a complete mapping and verification workflow.
