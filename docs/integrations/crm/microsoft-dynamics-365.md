# Microsoft Dynamics 365
Connect Microsoft Dynamics 365 to send enquiries to the records your team manages. You need access to configure the form in Craft and credentials for the destination account. The steps below establish the connection; finish by sending a test submission to verify the result.

:::warning
Ensure you have Azure administrator access or an Azure administrator is able to grant permissions to the application. This is required to approve the application in Microsoft Dynamics 365.
:::

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Microsoft Dynamics 365 as the **Integration Provider**.

### Step 2. Connect to the Microsoft Dynamics 365 API
1. Go to <a href="https://aad.portal.azure.com/" target="_blank">Azure Active Directory Admin Center</a> and login to your account.
1. Click on the **Azure Active Directory** in the left-hand sidebar, **App Registrations** then **New Registration**.
    - Under **Supported account types** select **Accounts in any organizational directory (Any Azure AD directory - Multitenant) and personal Microsoft accounts (e.g. Skype, Xbox)**.
    - In the **Redirect URI** field, enter the value from the **Redirect URI** field in Formie.
    - Click the **Register** button.
1. Copy the **Application (client) ID** from Microsoft Dynamics 365 and paste in the **Client ID** field in Formie.
1. Click on the **Certificates & Secrets** in the left-hand sidebar.
1. Click the **New client secret** button and provide a name and appropriate expiry.
1. Copy the **Value** from Microsoft Dynamics 365 for the resulting secret and paste in the **Client Secret** field below.
1. Click on the **API Permissions** in the left-hand sidebar.
1. Click the **Add a Permission** button.
1. Select **Microsoft Graph**, then the **Delegated permissions** option and select the following permissions:
    - `email`
    - `offline_access`
    - `openid`
    - `profile`
1. Click **Add Permissions**.
1. Navigate back to **All APIs** and select **Dynamics CRM** and select the following permissions:
    - `user_impersonation`
1. Click **Add Permissions**.
1. Navigate back to **API Permissions** and click the **Grant Admin Consent** button, and agree to the prompt.

### Step 3. Create an Application User
1. Go to <a href="https://admin.powerplatform.microsoft.com/" target="_blank">Power Platform Admin Center</a> and login to your account as a System Administrator.
1. Click **Environments** in the left-hand sidebar, and then select an environment from the list.
1. Copy the **Environment URL** from Power Platform and paste in the **Domain** field in Formie. Be sure to include the `https://` (e.g. `https://ffcor.crm6.dynamics.com`).
1. Click the **Settings** button in the top navigation.
1. Click **Users + Permissions**, and then click **Application Users**.
1. Click the **+ New app user** button in the top navigation.
1. Click **+ Add an app** to choose the registered Azure AD application that was created for the selected user, and then click the **Add** button.
1. Select a business unit from the dropdown list for **Business Unit**.
1. Click **Security roles** and add the following:
    - `Basic User`
1. Click the **Save** button, then the **Create** button.

### Step 4. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to Microsoft Dynamics 365, where you must approve Formie to access your Microsoft Dynamics 365 account.

### Step 5. Form Setting

1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Choose the data objects (record types) you want to use, and enable their mapping options where available.
1. Map the required destination fields using the variable picker. Follow the requirements shown for each selected record type.
1. Enable the integration.
1. Click **Save** to save the form.

For a contact enquiry, map the visitor’s email and name where those fields are available.

### Optional: Web API Version

The Microsoft Dynamics 365 Web API provides [different versions of the Web API](https://learn.microsoft.com/en-us/power-apps/developer/data-platform/webapi/web-api-versions). This is to both maintain compatibility or implement new breaking changes. There are no major differences between v9.0, v9.1 or v9.2 currently. This setting allows you to specify a specific API version if required. When setting a specific value, all Microsoft Dynamics 365 Web API requests will use this API version in the request URI.

For compatibility, the default setting is v9.0. This has been the value used in the Microsoft Dynamics 365 CRM integration prior to this being customisable.

### Optional: Impersonate User

When CRM records are created through Formie the user context of the account used to authenticate the OAuth connection is used (this is different to the application user). Depending on requirements, you may wish to override this. The easiest option is to authenticate under the account you wish to have records created by as, however this may not always be possible.

Using <a href="https://learn.microsoft.com/en-us/power-apps/developer/data-platform/webapi/impersonate-another-user-web-api#how-to-impersonate-a-user" target="_blank">user impersonation</a> you can set another systemuser context instead without changing the OAuth connection.

**Impersonation settings:**

* Impersonate user - Toggle the entire feature on or off.
* Impersonate header - This sets the HTTP header to either `CallerObjectId` or `MSCRMCallerID`. Depending on your environment one or the other will need to be used relative to the user ID provided.
* Impersonate User ID - This is the GUID of a valid systemuser within your Microsoft Dynamics 365 CRM.

When enabled this will be applied to all Microsoft Dynamics 365 CRM enabled forms.

By setting the impersonate HTTP header, this will also populate the Created By (delegate) field to the actual user context to provide a more accurate audit trail.

If you want to selectively control the "Created" By value on records per form, use the Created By field in the mapping.

**Note:** The impersonate user feature is set via a HTTP header on POST requests which will override any Created By field mapping that is set.

## Verify a Submission

Save the form, open it on your site and submit recognisable test values. Find the test record in the selected CRM destination. Check the mapped values and whether the integration created a record or updated an existing one as intended.

If nothing arrives, check whether integration conditions matched, whether the submission was complete and non-spam, and whether Craft’s queue has processed the job. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Connect and Test an Integration](/integrations/connect-and-test-an-integration) for a complete mapping and verification workflow.
