# Pardot
Follow the below steps to connect to the Pardot API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Pardot as the **Integration Provider**.

### Step 2. Connect to the Pardot API
1. Go to <a href="https://www.salesforce.com" target="_blank">Pardot</a> and login to your account.
1. In the main menu, on the top-right, click the **Settings** icon and select **Setup**.
1. In the left-hand sidebar, click on **Apps** → **App Manager**.
1. Click the **New Connected App** button.
1. Fill out all required fields.
1. In the **API (Enable OAuth Settings)** section, tick the **Enable OAuth Settings** checkbox.
    - In the **Callback URL** field, enter the value from the **Redirect URI** field in Formie.
    - In the **Selected OAuth Scopes** field, select the following permissions from the list and click **Add** arrow button:
        - **Manage Pardot services (pardot_api)**.
        - **Perform requests on your behalf at any time (refresh_token, offline_access)**.
    - Untick **Require Proof Key for Code Exchange (PKCE) Extension for Supported Authorization Flows**.
    - Tick **Require Secret for Web Server Flow**.
    - Untick **Require Secret for Refresh Token Flow**.
1. Click the **Save** button.
1. Copy the **Consumer Key** from Pardot and paste in the **Consumer Key** field in Formie.
1. Copy the **Consumer Secret** from Pardot and paste in the **Consumer Secret** field in Formie.
1. Click on the **Manage** button.
1. Click on the **Edit Policies** button.
1. In the **OAuth policies** section:
    - In the **Permitted Users** field, select **All users may self-authorize**.
    - In the **IP Relaxation** field, select **Relaxed IP restrictions**.
1. Click the **Save** button.
1. In the main menu, on the top-right, click the **Settings** icon and select **Setup**.
1. Enter **Business Unit** in the Quick Find box, and click on **Business Unit Setup**.
1. Copy the **Business Unit ID** from Pardot and paste in the **Business Unit ID** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to Pardot, where you must approve Formie to access your Pardot account.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.

## Troubleshooting

### Prospects are not created, or logs show API v4 errors

Formie uses the Pardot **API v4** endpoints. If your account still uses the legacy **Pardot Classic** app (`pi.pardot.com`), Pardot returns an error such as `Your account is unable to use version 4 of the API` (error code `89`).

Formie does not support the legacy Pardot Classic API. You will need an **Account Engagement** (Salesforce Pardot) business unit and OAuth connected app, as described above.

Check `storage/logs/formie.log` after a test submission for integration errors.
