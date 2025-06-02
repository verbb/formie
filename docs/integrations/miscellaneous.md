# Miscellaneous
Miscellaneous integrations are one of the provided integrations with Formie, and are used for a variety of different needs. These are integrations that don't otherwise fit into any other category.

Formie uses Craft‘s Queue system to send data to third-party providers. When a submission is successful a job is added to the queue so that it can be processed asynchronously.

You can create Miscellaneous integrations by going to **Formie** → **Settings** → **Miscellaneous**. You can pick from a list of providers Formie supports, and provide details for connecting to their respective APIs. You can also create multiple Miscellaneous integrations, in case you need to connect to multiple, different providers.

## Supported Providers
Formie integrates with the following providers:
- ClickUp
- Google Sheets
- Monday
- Recruitee
- Telegram
- Trello

:::tip
Is your provider not in the list above? [Contact us](https://verbb.io/contact) to submit your interest, or look at the [Custom Integration](docs:developers/custom-integration) docs to write your own provider support.
:::

## ClickUp
Follow the below steps to connect to the ClickUp API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select ClickUp as the **Integration Provider**.

### Step 2. Connect to the ClickUp API
1. Go to <a href="https://app.clickup.com" target="_blank">ClickUp</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Settings**.
1. In the left-hand sidebar, click **Apps**.
1. Click the **Generate** button.
1. Copy the **API Token** from ClickUp and paste in the **API Key** field in Formie.
1. Copy the **Workspace ID** from ClickUp's URL. e.g. `https://app.clickup.com/**9016531**`

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Google Sheets
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


## Monday
Follow the below steps to connect to the Monday API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Miscellaneous**.
1. Click the **New Integration** button.
1. Select Monday as the **Integration Provider**.

### Step 2. Connect to the Monday API
1. Go to <a href="https://monday.com/" target="_blank">Monday</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Administration**.
1. In the left-hand sidebar menu, click on **Connections**.
1. In the top menu, click on **API**.
1. Copy the **Personal API Token** from Monday and paste in the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Recruitee
Follow the below steps to connect to the Recruitee API.

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
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Trello
Follow the below steps to connect to the Trello API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Miscellaneous**.
1. Click the **New Integration** button.
1. Select Trello as the **Integration Provider**.

### Step 2. Connect to the Trello API
1. Go to the <a href="https://trello.com/app-key" target="_blank">Trello API Key</a> page.
1. Under the **Developer API Keys** heading, copy the **Key** value into the **Client ID** field in Formie.
1. Under the **Allowed Origins** heading, enter the value from the **Redirect URI** field in Formie into the text field under **New Allowed Origin** and hit **Submit**.
1. Under the **OAuth** heading, copy the **Secret** value into the **Client Secret** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to Trello, where you must approve Formie to access your Trello account.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Local Testing Proxy
Some integration providers are configured through OAuth, which involves a round trip from your Craft install, to the providers' authentication servers, and back again. For some providers - like Google - they require your Craft install to be on a public domain with SSL enabled. In practice, you might like to test out the integrations locally on your testing environment, which may not be on a publicly accessible domain.

Formie can help with this, by providing a **Proxy Redirect URI** for some integrations. What this does is modify the URL for the redirect to Verbb servers, to redirect back to your install.

For example, you might have a Redirect URI like the following:

```
http://formie.test/actions/formie/integrations/callback
```

Using this URL for providers won't work, as it'll detect `.test` is a non-public domain name. Using the Proxy Redirect URI will change the redirect URL to be:

```
https://proxy.verbb.io?return=http://formie.test/actions/formie/integrations/callback
```

Here, it routes the request through to our Verbb servers, which forwards on the request to the URL in the `return` parameter (which would be your local project).

You can also set this option via a `.env` variable to either `true` or `false`.

```
FORMIE_INTEGRATION_PROXY_REDIRECT="true"
```