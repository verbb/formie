# Google Sheets
Follow the below steps to connect to the Google Sheets API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Miscellaneous**.
1. Click the **New Integration** button.
1. Select Google Sheets as the **Integration Provider**.

### Step 2. Connect to the Google Sheets API
1. Go to the <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google API Console</a>.
1. Select an existing project or create a new one.
1. Go to the **APIs & Services** → **Library**  and enable the **Google Drive API** and **Google Sheets API** for the project.
1. Next, go to the **APIs & Services** → **Credentials** section.
1. Click **Create Credentials** → **OAuth client ID**.
    1. On the following page, select the **Application Type** as **Web application**.
    1. Provide a suitable **Name** so you can identify it in your Google account. This is not required by Formie.
    1. Under the **Authorized JavaScript origins**, click **Add URI** and enter your project's Site URL.
    1. Under the **Authorized redirect URIs**, click **Add URI** and enter the value from the **Redirect URI** field in Formie.
    1. Then click the **Create** button.
1. Once created, a popup will appear with your OAuth credentials. Copy the **Client ID** and **Client Secret** values and paste into the fields in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to Google, where you must approve Formie to access your Google account.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.
