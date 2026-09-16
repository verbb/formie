# Xero
Connect Xero to send enquiries to the records your team manages. You need access to configure the form in Craft and credentials for the destination account. The steps below establish the connection; finish by sending a test submission to verify the result.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Xero as the **Integration Provider**.

### Step 2. Connect to the Xero API
1. Go to <a href="https://developer.xero.com/" target="_blank">Xero Developer Portal</a> and login to your account.
1. Navigate to **My Apps**.
1. Click the **New App** button.
1. Select **Web App**.
1. In the **Redirect URI** field, enter the value from the **Redirect URI** field in Formie.
1. Navigate to **Configuration**.
1. Click the **Generate a Secret** button.
1. Copy the **Client ID** from Xero and paste in the **Client ID** field in Formie.
1. Copy the **Client Secret** from Xero and paste in the **Client Secret** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to Xero, where you must approve Formie to access your Xero account.

### Step 4. Form Setting

1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Choose the data objects (record types) you want to use, and enable their mapping options where available.
1. Map the required destination fields using the variable picker. Follow the requirements shown for each selected record type.
1. Enable the integration.
1. Click **Save** to save the form.

For a contact enquiry, map the visitor’s email and name where those fields are available.

## Verify a Submission

Save the form, open it on your site and submit recognisable test values. Find the test record in the selected CRM destination. Check the mapped values and whether the integration created a record or updated an existing one as intended.

If nothing arrives, check whether integration conditions matched, whether the submission was complete and non-spam, and whether Craft’s queue has processed the job. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Connect and Test an Integration](/integrations/connect-and-test-an-integration) for a complete mapping and verification workflow.
