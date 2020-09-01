# CRM Integrations
CRM integrations are one of the provided integrations with Formie, and are used for a variety of different needs. Mostly commonly, this integration pushes data related to “Contacts” and “Leads”. Each provider will have different names and available data available to be mapped. For instance, you might want to add someone to a “Potentials” list in your CRM, so you can follow up with later, or build complex automations.

Formie uses Craft‘s Queue system to send data to third-party providers. When a submission is successful a job is added to the queue so that it can be processed asynchronously.

<img src="https://verbb.io/uploads/plugins/formie/crm.png" />

You can create CRM integrations by going to **Formie** → **Settings** → **CRM**. You can pick from a list of providers Formie supports, and provide details for connecting to their respective APIs. You can also created multiple CRM integrations, in case you need to connect to multiple, different providers.

You can also test the connection to the APIs, to ensure that your site and Formie can communicate with the API.

Once created, enabled and connected, these integrations will be available to configure in your forms.

<img src="https://verbb.io/uploads/plugins/formie/crm-form.png" />

### Refresh Integration
Formie will fetch a number of data objects for the provider - each being specific to the provider. These objects are are cached for performance, you can also refresh the available data objects if they change.

#### Field Mapping
For each data object, Formie will also fetch all available fields, and any provider-specific fields for a particular CRM provider or data object. You can map which Formie fields should have their values connected to their third-party field counterpart. Each field mapping field can be opted-in, in case you don't require mapping content to all data objects.

## Supported Providers
Formie integrates with the following providers:
- ActiveCampaign
- Avochato
- Freshdesk
- HubSpot
- Infusionsoft
- Insightly
- Pipedrive
- Pipeliner
- Salesflare
- Salesforce
- Scoro
- vCita
- Zoho

:::tip
Is your provider not in the list above? [Contact us](https://verbb.io/contact) to submit your interest, or look at the [Custom Integration](docs:developers/custom-integration) docs to write your own provider support.
:::


## ActiveCampaign
Follow the below steps to connect to the ActiveCampaign API.

### Step 1. Connect to the ActiveCampaign API
1. Go to <a href="https://www.activecampaign.com/" target="_blank">ActiveCampaign</a> and login to your account.
1. In the left-hand menu, click **Settings**.
1. In the left-hand sub-menu, click **Developer**.
1. Copy the **API URL** from ActiveCampaign and paste in the **API URL** field in Formie.
1. Copy the **API Key** from ActiveCampaign and paste in the **API Key** field in Formie.

### Step 2. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 3. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Avochato
Follow the below steps to connect to the Avochato API.

### Step 1. Connect to the Avochato API
1. Go to <a href="https://www.avochato.com/" target="_blank">Avochato</a> and login to your account.
1. In the left-hand sidebar menu, click on **Settings**.
1. Click the **Generate Credentials** button.
1. Copy the **Auth ID** from Avochato and paste in the **Auth ID** field in Formie.
1. Copy the **Auth Secret** from Avochato and paste in the **Auth Secret** field in Formie.

### Step 2. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 3. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Freshdesk
Follow the below steps to connect to the Freshdesk API.

### Step 1. Connect to the Freshdesk API
1. Go to <a href="https://www.freshdesk.com/" target="_blank">Freshdesk</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Profile Settings**.
1. Copy the **API Key** from Freshdesk and paste in the **API Key** field in Formie.

### Step 2. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 3. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## HubSpot
Follow the below steps to connect to the HubSpot API.

### Step 1. Connect to the HubSpot API
1. Go to <a href="https://www.hubspot.com/" target="_blank">HubSpot</a> and login to your account.
1. Click on the settings icon on the top-right of the screen.
1. In the left-hand sidebar menu, click on **Integrations** → **API key**.
1. Copy the **API Key** from HubSpot and paste in the **API Key** field in Formie.

### Step 2. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 3. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Infusionsoft
Follow the below steps to connect to the Infusionsoft API.

### Step 1. Connect to the Infusionsoft API
1. Go to <a href="https://keys.developer.keap.com/accounts/login" target="_blank">Keap Developer Account</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Apps**.
1. Click the **+ New App** button.
1. Fill out the required details, and be sure to enable APIs (small green icon button).
1. Click the **Create** button.
1. Copy the **Key** from Infusionsoft and paste in the **Client ID** field in Formie.
1. Copy the **Secret** from Infusionsoft and paste in the **Client Secret** field in Formie.

### Step 2. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to Infusionsoft, where you must approve Formie to access your {name} account.

### Step 3. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Insightly
Follow the below steps to connect to the Insightly API.

### Step 1. Connect to the Insightly API
1. Go to <a href="https://www.insightly.com/" target="_blank">Insightly</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **User Settings**.
1. Copy the **API Key** from Insightly and paste in the **API Key** field in Formie.

### Step 2. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 3. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Pipedrive
Follow the below steps to connect to the Pipedrive API.

### Step 1. Connect to the Pipedrive API
1. Go to <a href="https://www.pipedrive.com/" target="_blank">Pipedrive</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Personal Preference**.
1. Click on the **API** tab.
1. Copy the **Your personal API token** from Pipedrive and paste in the **API Key** field in Formie.

### Step 2. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 3. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Pipeliner
Follow the below steps to connect to the Pipeliner API.

### Step 1. Connect to the Pipeliner API
1. Go to <a href="https://www.pipelinersales.com/" target="_blank">Pipeliner</a> and login to your account.
1. In the top main menu, click on **Menu** icon in the far-left of the screen (9 dots).
1. In the top sub-menu, click on the **Obtain API Key**.
1. Copy the **API Token** from {name} and paste in the **API Token** field in Formie.
1. Copy the **API Password** from Pipeliner and paste in the **API Password** field in Formie.
1. Copy the **API Space ID** from Pipeliner and paste in the **API Space ID** field in Formie.

### Step 2. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 3. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Salesflare
Follow the below steps to connect to the Salesflare API.

### Step 1. Connect to the Salesflare API
1. Go to <a href="https://salesflare.com/" target="_blank">Salesflare</a> and login to your account.
1. In the left-hand sidebar menu, click the **Settings** icon.
1. In the left-hand sidebar sub-menu, click on **API Keys**.
1. Click on the large **+** add button in the bottom-right of the screen.
1. Copy the **API Key** from Salesflare and paste in the **API Key** field in Formie.

### Step 2. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 3. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Salesforce
Follow the below steps to connect to the Salesforce API.

### Step 1. Connect to the Salesforce API
1. Go to <a href="https://www.salesforce.com" target="_blank">Salesforce</a> and login to your account.
1. In the main menu, on the top-right, click the **Settings** icon and select **Setup**.
1. In the left-hand sidebar, click on **Apps** → **App Manager**.
1. Click the **New Connected App** button.
1. Fill out all required fields.
1. In the **API (Enable OAuth Settings)** section, tick the **Enable OAuth Settings** checkbox.
    - In the **Callback URL** field, enter the value from the **Redirect URI** field in Formie.
    - In the **Selected OAuth Scopes** field, select the following permissions from the list and click **Add** arrow button:
        - **Allow access to your unique identifier (openid)**.
        - **Perform requests on your behalf at any time (refresh_token, offline_access)**.
1. Click the **Save** button.
1. Copy the **Consumer Key** from Salesforce and paste in the **Consumer Key** field in Formie.
1. Copy the **AConsumer Secret** from Salesforce and paste in the **Consumer Secret** field in Formie.
1. Click on the **Manage** button.
1. Click on the **Edit Policies** button.
1. In the **OAuth policies** section:
    - In the **Permitted Users** field, select **All users may self-authorize**.
    - In the **IP Relaxation** field, select **Relaxed IP restrictions**.
1. Click the **Save** button.

### Step 2. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to Salesforce, where you must approve Formie to access your Salesforce account.

### Step 3. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Scoro
Follow the below steps to connect to the Scoro API.

### Step 1. Connect to the Scoro API
1. Go to <a href="https://www.scoro.com/" target="_blank">Scoro</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Profile Settings**.
1. Click on **Site Settings** → **Integrations**.
1. Under the **General** heading, click on **Scoro API**.
1. Copy the **API Key** from Scoro and paste in the **API Key** field in Formie.

### Step 2. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 3. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## vCita
Follow the below steps to connect to the vCita API.

### Step 1. Connect to the vCita API
1. Go to <a href="https://www.vcita.com/" target="_blank">vCita</a> and login to your account.
1. In the left-hand sidebar menu, click on **Settings**.
1. Click on **Integrations**.
1. Find **Webhooks** and click on the **Connect** button.
1. Copy the **App Token** from vCita and paste in the **App Key** field in Formie.

### Step 2. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 3. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Zoho
Follow the below steps to connect to the ActiveCampaign API.

### Step 1. Connect to the Zoho API
1. Go to <a href="https://accounts.zoho.com/developerconsole" target="_blank">Zoho API Console</a> and login to your account.
1. Click the **Add Client** button.
1. Click **Server-based Applications**.
1. In the **Authorized Redirect URIs** field, enter the value from the **Redirect URI** field in Formie.
1. Copy the **Client ID** from Zoho and paste in the **Client ID** field in Formie.
1. Copy the **Client Secret** from Zoho and paste in the **Client Secret** field in Formie.

### Step 2. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to Zoho, where you must approve Formie to access your Zoho account.

### Step 3. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.



