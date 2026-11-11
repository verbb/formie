# Google Sheets
Connect Google Sheets to send submitted information to your connected account. You need access to configure the form in Craft and credentials for the destination account. The steps below establish the connection; finish by sending a test submission to verify the result.

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
1. Choose the destination supported by this integration.
1. Use the variable picker to map its required fields to the appropriate form answers.
1. Enable the integration.
1. Click **Save** to save the form.

## Local Testing Proxy

Connecting to Google sends you to Google to approve access, then back to your site's **Redirect URI**. Google only accepts redirect URIs on a public domain, so it rejects a local development address such as `https://my-site.ddev.site/actions/formie/integrations/callback`.

To connect from a local site, enable **Proxy Redirect URI** in the integration settings. Formie then routes the redirect through Verbb's proxy server, which sends you back to your local site, and the **Redirect URI** field changes to a value like this:

```
https://proxy.verbb.io?return=https://my-site.ddev.site/actions/formie/integrations/callback
```

Enter this updated value under **Authorized redirect URIs** in the Google API Console, then save the integration and click **Connect** again.

Only use the proxy for local testing. On staging and production, turn it off and use your site's own Redirect URI. The setting accepts an environment variable, so you can set it to `true` in your local `.env` file and `false` everywhere else.

## Verify a Submission

Save the form, open it on your site and submit recognisable test values. Find the resulting item in the destination you selected and compare its values with the test submission.

If nothing arrives, check whether integration conditions matched, whether the submission was complete and non-spam, and whether Craft’s queue has processed the job. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Integrations Overview](/integrations/overview) for the shared connection, mapping and verification workflow.
