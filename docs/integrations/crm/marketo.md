# Marketo
Connect Marketo to send enquiries to the records your team manages. You need access to configure the form in Craft and credentials for the destination account. The steps below establish the connection; finish by sending a test submission to verify the result.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Marketo as the **Integration Provider**.

### Step 2. Connect to the Marketo API
1. Go to <a href="https://business.adobe.com/au/products/marketo.html" target="_blank">Marketo</a> and login to your account.
1. Under **Integration**, select **LaunchPoint**.
1. Click **New** → **New Service**.
1. Enter a name for the service and select **Custom** as the service type.
1. Choose the appropriate API-only user and click **Create**.
1. Still in the Admin panel, go to **Web Services** under **Integration**.
1. Under **REST API**, copy the **Endpoint URL** and paste in the **API Domain** field in Formie. If the URL ends with `/rest`, Formie will normalise it automatically — OAuth uses the account root domain.
1. Copy the **Client ID** from Marketo and paste in the **Client ID** field in Formie.
1. Copy the **Client Secret** from Marketo and paste in the **Client Secret** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar. Marketo uses a client-credentials connection, so you will not be redirected to Marketo to approve access.

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
