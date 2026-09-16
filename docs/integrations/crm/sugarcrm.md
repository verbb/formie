# SugarCRM
Connect SugarCRM to send enquiries to the records your team manages. You need access to configure the form in Craft and credentials for the destination account. The steps below establish the connection; finish by sending a test submission to verify the result.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select SugarCRM as the **Integration Provider**.

### Step 2. Connect to the SugarCRM API
1. Go to <a href="https://sugarcrm.com/" target="_blank">SugarCRM</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Admin**.
1. Find and click the **Configure API Platforms** link.
1. Below the table of API Platforms, enter `formie` in the add field, and click the **Add** button. This will add `formie` to the table of platforms.
1. Enter the username for your SugarCRM account in the **Username** field in Formie.
1. Enter the password for your SugarCRM account in the **Password** field in Formie.
1. Enter the full domain (including `https://`) for your SugarCRM account in the **Domain** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.

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
