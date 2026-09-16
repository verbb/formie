# Recruitee
Connect Recruitee to send submitted information to your connected account. You need access to configure the form in Craft and credentials for the destination account. The steps below establish the connection; finish by sending a test submission to verify the result.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Miscellaneous**.
1. Click the **New Integration** button.
1. Select Recruitee as the **Integration Provider**.

### Step 2. Connect to the Recruitee API
1. Go to <a href="https://app.recruitee.com/" target="_blank">Recruitee</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Profile Settings**.
1. In the left-hand sidebar sub-menu, click on **Apps and Plugins** → **Personal API Tokens**.
1. Click the **New Token** button and provide a name.
1. Copy the **API Key** from Recruitee and paste in the **API Key** field in Formie.
1. Copy the **Subdomain** from Recruitee and paste in the **Subdomain** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

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
