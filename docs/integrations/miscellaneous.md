# Miscellaneous
Miscellaneous integrations are one of the provided integrations with Formie, and are used for a variety of different needs. These are integrations that don't otherwise fit into any other category.

Formie uses Craft‘s Queue system to send data to third-party providers. When a submission is successful a job is added to the queue so that it can be processed asynchronously.

You can create Miscellaneous integrations by going to **Formie** → **Settings** → **Miscellaneous**. You can pick from a list of providers Formie supports, and provide details for connecting to their respective APIs. You can also created multiple Miscellaneous integrations, in case you need to connect to multiple, different providers.

## Supported Providers
Formie integrates with the following providers:
- Google Sheets
- Monday
- Slack
- Trello

:::tip
Is your provider not in the list above? [Contact us](https://verbb.io/contact) to submit your interest, or look at the [Custom Integration](docs:developers/custom-integration) docs to write your own provider support.
:::


## Google Sheets
Follow the below steps to connect to the Google Sheets API.

### Step 1. Connect to the Google Sheets API
1. Go to the <a href="https://console.developers.google.com/cloud-resource-manager" target="_blank">Google API Console</a>.
1. Select an existing project or create a new one.
1. Go to the **API Manager** and enable the **Google Drive API** and **Google Sheets API** for the project.
1. Next, go to the **API Manager** → **Credentials** section.
1. Click **Create Credentials** → **OAuth 2.0 client ID**.
    1. In the popup select the **Application Type** as **Web application**.
    1. In the field **Authorized redirect URI**, enter the value from the **Redirect URI** field in Formie.
    1. Then click the **Create Client ID** button, then navigate to API Keys section.
1. After the popup closes copy the **Client ID** and **Client Secret** values and paste into the fields in Formie.

### Step 2. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to Google, where you must approve Formie to access your Google account.

### Step 3. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Monday
Follow the below steps to connect to the Monday API.

### Step 1. Connect to the Monday API
1. Go to <a href="https://app.ontraport.com/" target="_blank">Monday</a> and login to your account.
1. Click on your profile dropdown on the bottom-left of the screen, and select **Admin**.
1. In the left-hand sidebar menu, click on **API**.
1. Copy the **API v2 Token** from Monday and paste in the **API Key** field in Formie.

### Step 2. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 3. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Slack
Follow the below steps to connect to the Slack API.

### Step 1. Connect to the Slack API
1. Go to the <a href="https://api.slack.com/apps?new_app=1" target="_blank">Slack App Center</a>.
1. Create a new app, by entering an **App Name** and **Development Slack Workspace**.
1. In the left-hand sidebar, under **Settings**, click **Basic Information**.
1. Under the **App Credentials** section, copy the **Client ID** and **Client Secret** values and paste into the fields in Formie.
1. In the left-hand sidebar, under **Features**, click **OAuth & Permissions**.
1. In the section **Redirect URLs**, click the **Add New Redirect URL** button and enter the value from the **Redirect URI** field in Formie.
1. Then click the **Add** button, then click the **Save URLs** button.

### Step 2. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to Slack, where you must approve Formie to access your Slack account.

### Step 3. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Trello
Follow the below steps to connect to the Trello API.

### Step 1. Connect to the Trello API
1. Go to the <a href="https://trello.com/app-key" target="_blank">Trello API Key</a> page.
1. Under the **Developer API Keys** heading, copy the **Key** value into the **Client ID** field in Formie.
1. Under the **Allowed Origins** heading, enter the value from the **Redirect URI** field in Formie into the text field under **New Allowed Origin** and hit **Submit**.
1. Under the **OAuth** heading, copy the **Secret** value into the **Client Secret** field in Formie.

### Step 2. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to Trello, where you must approve Formie to access your Trello account.

### Step 3. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


