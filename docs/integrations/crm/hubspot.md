# HubSpot
Follow the below steps to connect to the HubSpot API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select HubSpot as the **Integration Provider**.

### Step 2. Connect to the HubSpot API
1. Go to <a href="https://www.hubspot.com/" target="_blank">HubSpot</a> and login to your account.
1. Click on the settings icon on the top-right of the screen.
1. In the left-hand sidebar menu, click on **Integrations** → **Private Apps**.
1. Click the **Create a private app** button.
1. Fill out the details, and click the **Scopes** tab. Select the following scopes:
    - `crm.lists.read`
    - `crm.objects.companies.read`
    - `crm.objects.companies.write`
    - `crm.objects.contacts.read`
    - `crm.objects.contacts.write`
    - `crm.objects.deals.read`
    - `crm.objects.deals.write`
    - `crm.schemas.custom.read`
    - `tickets`
    - `forms`
1. Click the **Create App** button in the top right.
1. In the dialog box, review the info about your app's access token, then click **Continue creating**.
1. Copy the **Access Token** from HubSpot and paste in the **Access Token** field in Formie.

### Step 3. Disable Automatic Form Collection
1. In HubSpot, click on the **Settings** cog icon in the top right-hand of the screen.
1. In the left-hand sidebar menu, click on **Marketing** → **Forms**.
1. Click the **Non-Hubspot Forms** button.
1. For **Collect data from website forms** ensure that this is switched to **Off**.

### Step 4. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 5. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.

### Custom objects (Enterprise)

When using **Map to Form** with HubSpot custom object properties, Formie fetches your portal’s custom object schemas when you refresh HubSpot forms. Form fields are mapped using the correct `objectTypeId` (for example `2-21479350`) automatically.

Ensure your private app includes the `crm.schemas.custom.read` scope, and refresh HubSpot forms after creating or changing custom objects in HubSpot.
