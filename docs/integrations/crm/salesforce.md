# Salesforce
Connect Salesforce to send enquiries to the records your team manages. You need access to configure an External Client App in Salesforce and an integration user with permission to work with the destination records.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click **New Integration**.
1. Select Salesforce as the **Integration Provider**.

### Step 2. Choose an OAuth Grant
Salesforce supports several OAuth grants. Choose **Authorization Code** when an authorised Salesforce user can complete an interactive browser login. Choose **Client Credentials** for a server-to-server connection that always acts as a designated Salesforce integration user.

The **Password** grant remains available for existing integrations, but Salesforce recommends moving away from username-password authentication. Use one of the other grants for a new connection.

### Step 3. Configure an External Client App
Go to Salesforce **Setup** → **External Client Apps Manager**, create an External Client App and enable OAuth. Copy Formie’s **Redirect URI** into Salesforce’s **Callback URL** field when the field is required.

For either supported grant, add **Manage user data via APIs (api)** to the selected OAuth scopes. Authorization Code connections should also add **Access unique user identifiers (openid)** and **Perform requests at any time (refresh_token, offline_access)**.

#### Authorization Code
Leave **Enable Client Credentials Flow** disabled unless another system also uses that flow. You can enable **Require Proof Key for Code Exchange (PKCE) extension for Supported Authorization Flows**; Formie sends the PKCE challenge and verifier during the connection.

Configure the app’s policies to allow the Salesforce user who will click Formie’s **Connect** button to authorise the app. The callback URL must exactly match the **Redirect URI** shown by Formie.

#### Client Credentials
Under **Flow Enablement**, select **Enable Client Credentials Flow**. After creating the app, open its policies, enable the Client Credentials flow and choose a **Run As** user. Every Salesforce API request from Formie uses that user’s permissions, so give the account only the access required by the mapped objects and fields.

Copy the Current My Domain URL from Salesforce’s **My Domain** settings. It normally resembles `https://example.my.salesforce.com` and must not include `/services/oauth2/token`; Formie adds the token endpoint path.

### Step 4. Enter the Credentials in Formie
Copy the External Client App’s **Consumer Key** and **Consumer Secret** into the corresponding Formie fields, then select the matching **Grant**.

For Client Credentials, enter the Salesforce My Domain URL in **Authentication Domain**. You can leave this field empty to use `https://login.salesforce.com`, or `https://test.salesforce.com` when **Use Sandbox** is enabled.

For the Password grant, enter the Salesforce username and password. These fields are not used by Client Credentials because Salesforce obtains the user identity from the app’s **Run As** policy.

### Step 5. Test the Connection
Save the integration and click **Connect** in the right-hand sidebar.

Authorization Code opens Salesforce so the user can sign in and approve Formie. Client Credentials obtains a token directly and returns to the integration without displaying Salesforce’s authorisation screen. A successful connection confirms that Salesforce issued a token; the submission test below confirms that the Run As user can access the selected records.

### Step 6. Configure the Form
1. Go to the form you want to connect.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Choose the Salesforce objects you want to use and enable their mapping options where available.
1. Map the required destination fields using the variable picker.
1. Enable the integration.
1. Click **Save**.

For a contact enquiry, map the visitor’s email and name where those fields are available.

## Verify a Submission
Save the form, open it on your site and submit recognisable test values. Find the test record in Salesforce and check the mapped values and whether Formie created a record or updated an existing one as intended.

If nothing arrives, confirm that the integration conditions matched, the submission was complete and non-spam, and Craft’s queue processed the job. For Client Credentials, also check that the External Client App still has a Run As user and that the user can access every enabled Salesforce object. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Connect and Test an Integration](/integrations/connect-and-test-an-integration) for a complete mapping and verification workflow.
