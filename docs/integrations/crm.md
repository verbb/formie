# CRM
CRM integrations are one of the provided integrations with Formie, and are used for a variety of different needs. Mostly commonly, this integration pushes data related to “Contacts” and “Leads”. Each provider will have different names and available data available to be mapped. For instance, you might want to add someone to a “Potentials” list in your CRM, so you can follow up with later, or build complex automations.

Formie uses Craft‘s Queue system to send data to third-party providers. When a submission is successful a job is added to the queue so that it can be processed asynchronously.

<img src="https://assets.verbb.io/plugins/formie/crm.png" />

You can create CRM integrations by going to **Formie** → **Settings** → **CRM**. You can pick from a list of providers Formie supports, and provide details for connecting to their respective APIs. You can also create multiple CRM integrations, in case you need to connect to multiple, different providers.

You can also test the connection to the APIs, to ensure that your site and Formie can communicate with the API.

Once created, enabled and connected, these integrations will be available to configure in your forms.

<img src="https://assets.verbb.io/plugins/formie/crm-form.png" />

## Refresh Integration
Formie will fetch a number of data objects for the provider - each being specific to the provider. These objects are cached for performance, you can also refresh the available data objects if they change.

## Field Mapping
For each data object, Formie will also fetch all available fields, and any provider-specific fields for a particular CRM provider or data object. You can map which Formie fields should have their values connected to their third-party field counterpart. Each field mapping field can be opted-in, in case you don't require mapping content to all data objects.

## Supported Providers
Formie integrates with the following providers:
- 1CRM
- ActiveCampaign
- Agile CRM
- Attio
- Avochato
- Capsule CRM
- CiviCRM
- Copper CRM
- Dotdigital
- Flowlu
- Freshsales
- HubSpot
- Infusionsoft
- Iterable
- Insightly
- Klaviyo
- Marketo
- Maximizer
- Mercury
- Microsoft Dynamics 365
- NoCRM
- Outseta
- Pardot
- Pipedrive
- Pipeliner
- Procurios
- Salesflare
- Salesforce
- Salesmate
- Scoro
- SharpSpring
- SugarCRM
- SuiteCRM
- vCita
- Xero
- Zoho

:::tip
Is your provider not in the list above? [Contact us](https://verbb.io/contact) to submit your interest, or look at the [Custom Integration](docs:developers/custom-integration) docs to write your own provider support.
:::

## 1CRM
Follow the below steps to connect to the 1CRM API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select 1CRM as the **Integration Provider**.

### Step 2. Connect to the 1CRM API
1. Go to <a href="https://1crm.com" target="_blank">1CRM</a> and login to your account.
1. Click on the **Admin** dropdown on the top-right of the screen, and select **Administration**.
1. Click the **API Clients** link.
1. Click the **Create** button.
1. Select **Authorization Code** for the **Enabled Grant Types**.
1. Copy the **ID** from 1CRM and paste in the **Client ID** field in Formie.
1. Click the **Change API Secret** button and enter a value.
1. Copy the **Secret** from 1CRM and paste in the **Client Secret** field in Formie.
1. In the **Redirect URL** field, enter the value from the **Redirect URI** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to 1CRM, where you must approve Formie to access your 1CRM account.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## ActiveCampaign
Follow the below steps to connect to the ActiveCampaign API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select ActiveCampaign as the **Integration Provider**.

### Step 2. Connect to the ActiveCampaign API
1. Go to <a href="https://www.activecampaign.com/" target="_blank">ActiveCampaign</a> and login to your account.
1. In the left-hand menu, click **Settings**.
1. In the left-hand sub-menu, click **Developer**.
1. Copy the **API URL** from ActiveCampaign and paste in the **API URL** field in Formie.
1. Copy the **API Key** from ActiveCampaign and paste in the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Agile CRM
Follow the below steps to connect to the Agile CRM API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Agile CRM as the **Integration Provider**.

### Step 2. Connect to the Agile CRM API
1. Go to <a href="https://www.agilecrm.com/" target="_blank">Agile CRM</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Admin Settings**.
1. Click on the **Developers & API** menu in the left-hand sidebar.
1. Copy the **REST API** from Agile CRM and paste in the **API Key** field in Formie.
1. Enter the email for your Agile CRM account in the **API Email** field in Formie.
1. Enter the full domain (including `https://`) for your Agile CRM account in the **Domain** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Attio
Follow the below steps to connect to the Attio API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Attio as the **Integration Provider**.

### Step 2. Connect to the Attio API
1. Go to <a href="https://www.avochato.com/" target="_blank">Attio</a> and login to your account.
1. Click on your profile dropdown on the top-left of the screen, and select **Account Settings**.
1. In the left-hand sidebar menu, click on **Developers**.
1. Click the **Create Access Token** button.
1. Provide **Read-Write** access to all permissions.
1. Click the **Save Changes** button.
1. Copy the **Access Token** from Attio and paste in the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Avochato
Follow the below steps to connect to the Avochato API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Avochato as the **Integration Provider**.

### Step 2. Connect to the Avochato API
1. Go to <a href="https://www.avochato.com/" target="_blank">Avochato</a> and login to your account.
1. In the left-hand sidebar menu, click on **Settings**.
1. Click the **Generate Credentials** button.
1. Copy the **Auth ID** from Avochato and paste in the **Auth ID** field in Formie.
1. Copy the **Auth Secret** from Avochato and paste in the **Auth Secret** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Capsule
Follow the below steps to connect to the Capsule API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Capsule as the **Integration Provider**.

### Step 2. Connect to the Capsule API
1. Click on your profile dropdown on the top-right of the screen, and select **My Preferences**.
1. Click the **API Authentication Tokens** button.
1. Click the **Generate new API token** button.
1. Copy the **API Key** from Capsule and paste in the **API Key** in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## CiviCRM
Follow the below steps to connect to the CiviCRM API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select CiviCRM as the **Integration Provider**.

### Step 2. Connect to the CiviCRM API
1. Go to your CiviCRM system and login to your account. This may be self-hosted or with a provider.
1. Ensure that you have the **API Key Management** extension installed.
1. In the top menu, navigate to **Administer** > **Users and Permissions** > **User Accounts**.
1. For an administrator, click the **Linked Contact** column value.
1. Click the **API Key** tab.
1. Click the **Add API Key** button, and **Generate**.
1. Copy the **API Key** from CiviCRM and paste in the **API Key** in Formie.
1. Copy the **Site Key** from CiviCRM and paste in the **Site Key** in Formie.
1. Enter the full domain (including `https://`) for your CiviCRM install in the **Domain** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Copper
Follow the below steps to connect to the Copper API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Copper as the **Integration Provider**.

### Step 2. Connect to the Copper API
1. Go to <a href="https://app.copper.com/" target="_blank">Copper</a> and login to your account.
1. Click on the **Settings** menu in the left-hand sidebar.
1. Click on the **Integration** menu in the left-hand sidebar and select **API Keys**.
1. Click the **Generate API Key** button.
1. Copy the **API Key** from Copper and paste in the **API Key** field in Formie.
1. Enter the email for your Copper account in the **API Email** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Dotdigital
Follow the below steps to connect to the Dotdigital API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Dotdigital as the **Integration Provider**.

### Step 2. Connect to the Dotdigital API
1. Go to <a href="https://dotdigital.com/" target="_blank">Dotdigital</a> and login to your account.
1. Click the more-options (triple dots) in the bottom left corner and go to **Access** > **API users**.
1. Click the **New user** button.
1. Copy the **Email Address** from Dotdigital and paste in the **Username** field in Formie.
1. Create a password and copy this from Dotdigital and paste in the **Password** field in Formie.
1. Click the **Save** button.
1. Copy the **API Endpoint** from Dotdigital and paste in the **API Endpoint** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Flowlu
Follow the below steps to connect to the Flowlu API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Flowlu as the **Integration Provider**.

### Step 2. Connect to the Flowlu API
1. Go to <a href="https://flowlu.com/" target="_blank">Flowlu</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Portal Settings**.
1. In the left-hand sidebar, click **API Settings**.
1. Click the **Add** button.
1. Select all applications and click the **Save** button.
1. Copy the **API Key** from Flowlu and paste in the **API Key** field in Formie.
1. Enter the full domain (including `https://`) for your Flowlu account in the **Domain** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Freshsales
Follow the below steps to connect to the Freshdesk API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Freshsales as the **Integration Provider**.

### Step 2. Connect to the Freshdesk API
1. Go to <a href="https://www.freshworks.com/freshsales-crm/login/" target="_blank">Freshsales</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Settings**.
1. Click on the **API Settings** button.
1. Copy the **API Key** from Freshdesk and paste in the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## HubSpot
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


## Infusionsoft
Follow the below steps to connect to the Infusionsoft API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Infusionsoft as the **Integration Provider**.

### Step 2. Connect to the Infusionsoft API
1. Go to <a href="https://keys.developer.keap.com/accounts/login" target="_blank">Keap Developer Account</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Apps**.
1. Click the **+ New App** button.
1. Fill out the required details, and be sure to enable APIs (small green icon button).
1. Click the **Create** button.
1. Copy the **Key** from Infusionsoft and paste in the **Client ID** field in Formie.
1. Copy the **Secret** from Infusionsoft and paste in the **Client Secret** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to Infusionsoft, where you must approve Formie to access your Infusionsoft account.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Insightly
Follow the below steps to connect to the Insightly API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Insightly as the **Integration Provider**.

### Step 2. Connect to the Insightly API
1. Go to <a href="https://www.insightly.com/" target="_blank">Insightly</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **User Settings**.
1. Copy the **API Key** from Insightly and paste in the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Iterable
Follow the below steps to connect to the Iterable API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Iterable as the **Integration Provider**.

### Step 2. Connect to the Iterable API
1. Go to <a href="https://app.iterable.com" target="_blank">Iterable</a> and login to your account.
1. In the top menu, click **Integrations** and **API Keys**.
1. Click on the **New API Key** button.
1. Enter a **Name** and select **Server-side** for the **Type**.
1. Copy the **API Key** from Iterable and paste in the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Klaviyo
Follow the below steps to connect to the Klaviyo API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Klaviyo as the **Integration Provider**.

### Step 2. Connect to the Klaviyo API
1. Go to <a href="https://www.klaviyo.com/account" target="_blank">Klaviyo</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Account**.
1. Click the **Settings** dropdown and click **API Keys**.
1. Copy the **Public API Key** from Klaviyo and paste in the **Public API Key** field in Formie.
1. Click the **Create Private API Key** button.
1. Copy the **Private API Key** from Klaviyo and paste in the **Private API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Marketo
Follow the below steps to connect to the Marketo API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Marketo as the **Integration Provider**.

### Step 2. Connect to the Marketo API
1. Go to <a href="https://business.adobe.com/au/products/marketo.html" target="_blank">Marketo</a> and login to your account.
1. Under **Integration**, select **LaunchPoint**.
1. Click **New** → **New Service**.
1. Enter a name for the service and select **Custom** as the service type.
1. Choose the appropriate API-only user and click **Create**.
1. Still in the Admin panel, go to **Web Services** under **Integration**.
1. Under **REST API**, copy the **Endpoint URL** and paste in the **API Domain** field in Formie.
1. Copy the **Client ID** from Marketo and paste in the **Client ID** field in Formie.
1. Copy the **Client Secret** from Marketo and paste in the **Client Secret** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to Marketo, where you must approve Formie to access your Marketo account.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Maximizer
Follow the below steps to connect to the Maximizer API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Maximizer as the **Integration Provider**.

### Step 2. Connect to the Maximizer API
1. Go to <a href="https://www.maximizer.com/support/crm-api/" target="_blank">Maximizer CRM API</a> and request access to their API.
1. When approved, you'll receive your details via email.
1. Enter your **Username** from Maximizer and paste in the **Username** field in Formie.
1. Enter your **Password** from Maximizer and paste in the **Password** field in Formie.
1. Copy the **Web Access URL** from Maximizer and paste in the **Web Access URL** field in Formie.
1. Copy the **Database ID** from Maximizer and paste in the **Database ID** field in Formie.
1. Copy the **Vendor ID** from Maximizer and paste in the **Vendor ID** field in Formie.
1. Copy the **App Key** from Maximizer and paste in the **App Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Mercury (by Connective)
Follow the below steps to connect to the Connective Mercury API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Mercury as the **Integration Provider**.

### Step 2. Connect to the Connective Mercury API
1. Go to <a href="https://www.connective.com.au/" target="_blank">Connective</a> and login to your Mercury account.
1. In the top menu, click the **Admin** tab.
1. In the left-hand sidebar menu, click the **Integrations** tab.
1. Copy the **API Key** from Mercury and paste in the **API Key** field in Formie.
1. Copy the **API Token** from Mercury and paste in the **API Token** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Microsoft Dynamics 365
Follow the below steps to connect to the Microsoft Dynamics 365 API.

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
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.

### Optional: Web API version

The Microsoft Dynamics 365 Web API provides [different versions of the Web API](https://learn.microsoft.com/en-us/power-apps/developer/data-platform/webapi/web-api-versions). This is to both maintain compatibility or implement new breaking changes. There are no major differences between v9.0, v9.1 or v9.2 currently. This setting allows you to specify a specific API version if required. When setting a specific value, all Microsoft Dynamics 365 Web API requests will use this API version in the request URI.

For compatibility, the default setting is v9.0. This has been the value used in the Microsoft Dynamics 365 CRM integration prior to this being customisable.

### Optional: Impersonate user

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


## NoCRM
Follow the below steps to connect to the NoCRM API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select NoCRM as the **Integration Provider**.

### Step 2. Connect to the NoCRM API
1. Go to <a href="https://nocrm.io/" target="_blank">NoCRM</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Admin Panel**.
1. Under **Integrations** click on the **API Keys** button.
1. Click the **Create API Key** button.
1. Copy the **API Key** from NoCRM and paste in the **API Key** field in Formie.
1. Enter the full domain (including `https://`) for your NoCRM account in the **Domain** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Outseta
Follow the below steps to connect to the Outseta API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Outseta as the **Integration Provider**.

### Step 2. Connect to the Outseta API
1. Go to <a href="https://outseta.com/" target="_blank">Outseta</a> and login to your account.
1. In the left-hand sidebar, click on **Settings** → **Integrations**.
1. Click the **API Keys** item.
1. Click the **Add API Key** button.
1. Copy the **Key** from Outseta and paste in the **API Key** field in Formie.
1. Copy the **Secret** from Outseta and paste in the **Secret Key** field in Formie.
1. Enter the full domain (including `https://`) for your Outseta account in the **Domain** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Pardot
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


## Pipedrive
Follow the below steps to connect to the Pipedrive API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Pipedrive as the **Integration Provider**.

### Step 2. Connect to the Pipedrive API
1. Go to <a href="https://www.pipedrive.com/" target="_blank">Pipedrive</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Personal Preferences**.
1. Click on the **API** tab.
1. Copy the **Your personal API token** from Pipedrive and paste in the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Pipeliner
Follow the below steps to connect to the Pipeliner API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Pipeliner as the **Integration Provider**.

### Step 2. Connect to the Pipeliner API
1. Go to <a href="https://www.pipelinersales.com/" target="_blank">Pipeliner</a> and login to your account.
1. In the top main menu, click on **Menu** icon in the far-left of the screen (9 dots).
1. In the top sub-menu, click on the **Obtain API Key**.
1. Copy the **API Token** from Pipeliner and paste in the **API Token** field in Formie.
1. Copy the **API Password** from Pipeliner and paste in the **API Password** field in Formie.
1. Copy the **API Space ID** from Pipeliner and paste in the **API Space ID** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Procurios
Follow the below steps to connect to the Marketo API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Procurios as the **Integration Provider**.

### Step 2. Connect to the Procurios API
1. Go to <a href="https://www.procurios.com/en/" target="_blank">Procurios</a> and login to your account.
1. Go to the **Settings** or **Developers** section.
1. Locate the area to manage **OAuth Applications**.
1. Register a new application.
1. In the **Redirect URI** field, enter the value from the **Redirect URI** field in Formie.
1. Copy the **Client ID** from Procurios and paste in the **Client ID** field in Formie.
1. Copy the **Client Secret** from Procurios and paste in the **Client Secret** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to Procurios, where you must approve Formie to access your Procurios account.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Salesflare
Follow the below steps to connect to the Salesflare API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Salesflare as the **Integration Provider**.

### Step 2. Connect to the Salesflare API
1. Go to <a href="https://salesflare.com/" target="_blank">Salesflare</a> and login to your account.
1. In the left-hand sidebar menu, click the **Settings** icon.
1. In the left-hand sidebar sub-menu, click on **API Keys**.
1. Click on the large **+** add button in the bottom-right of the screen.
1. Copy the **API Key** from Salesflare and paste in the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Salesforce
Follow the below steps to connect to the Salesforce API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Salesforce as the **Integration Provider**.

### Step 2. Connect to the Salesforce API
1. Go to <a href="https://www.salesforce.com" target="_blank">Salesforce</a> and login to your account.
1. In the main menu, on the top-right, click the **Settings** icon and select **Setup**.
1. In the left-hand sidebar, click on **Apps** → **App Manager**.
1. Click the **New Connected App** button.
1. Fill out all required fields.
1. In the **API (Enable OAuth Settings)** section, tick the **Enable OAuth Settings** checkbox.
    - In the **Callback URL** field, enter the value from the **Redirect URI** field in Formie.
    - In the **Selected OAuth Scopes** field, select the following permissions from the list and click **Add** arrow button:
        - **Manage user data via APIs (api)**
        - **Access unique user identifiers (openid)**
        - **Perform requests at any time (refresh_token, offline_access)**
    - Untick **Require Proof Key for Code Exchange (PKCE) Extension for Supported Authorization Flows**.
    - Tick **Require Secret for Web Server Flow**.
    - Untick **Require Secret for Refresh Token Flow**.
1. Click the **Save** button.
1. Copy the **Consumer Key** from Salesforce and paste in the **Consumer Key** field in Formie.
1. Copy the **Consumer Secret** from Salesforce and paste in the **Consumer Secret** field in Formie.
1. Click on the **Manage** button.
1. Click on the **Edit Policies** button.
1. In the **OAuth policies** section:
    - In the **Permitted Users** field, select **All users may self-authorize**.
    - In the **IP Relaxation** field, select **Relax IP restrictions**.
    - In the **Refresh Token Policy** field, select **Refresh token is valid until revoked**.
1. In the **Session Policies** section:
    - Untick **High assurance session required**.
1. Click the **Save** button.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to Salesforce, where you must approve Formie to access your Salesforce account.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Salesmate
Follow the below steps to connect to the Salesmate API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Salesmate as the **Integration Provider**.

### Step 2. Connect to the Salesmate API
1. Go to <a href="https://salesmate.io/" target="_blank">Salesmate</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **My Account**.
1. In the left-hand sidebar, click on **Access Key**.
1. Copy the **Session Key / Session Token** from Salesmate and paste in the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Scoro
Follow the below steps to connect to the Scoro API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Scoro as the **Integration Provider**.

### Step 2. Connect to the Scoro API
1. Go to <a href="https://www.scoro.com/" target="_blank">Scoro</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Profile Settings**.
1. Click on **Site Settings** → **Integrations**.
1. Under the **General** heading, click on **Scoro API**.
1. Copy the **API Key** from Scoro and paste in the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## SharpSpring
Follow the below steps to connect to the SharpSpring API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select SharpSpring as the **Integration Provider**.

### Step 2. Connect to the SharpSpring API
1. Go to <a href="https://sharpspring.com/" target="_blank">SharpSpring</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Settings**.
1. In the left-hand sidebar menu, click on **API Settings**.
1. Click the **Generate New API Keys** button.
1. Copy the **Account ID** from SharpSpring and paste in the **Account ID** field in Formie.
1. Copy the **Secret Key** from SharpSpring and paste in the **Secret Key** field in Formie.

### Step 3. Provide Form Base URL
1. If you do not wish to map Formie Submission to a SharpSpring form, you can skip this step.
1. Click **Marketing** > **Content** > **Forms** in SharpSpring's top toolbar.
1. Click the **Create Form** button.
1. Enter a name for the form and select the **Native Form** radio button. Click the **Continue** button.
1. On the next screen, you'll be presented with embed instructions. We want to extract two bits of information.
1. Seach for the line `__ss_noform.push(['baseURI', 'https://app-xxxx.marketingautomation.services/webforms/receivePostback/xxxx/']);`
1. Copy the _just_ the **URL** value from the embed code (between the single quotes) and paste in the **Form URL** field below.

### Step 4. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 5. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## SugarCRM
Follow the below steps to connect to the SugarCRM API.

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
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## SuiteCRM
Follow the below steps to connect to the SuiteCRM API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select SuiteCRM as the **Integration Provider**.

### Step 2. Connect to the SuiteCRM API
1. Login to your SuiteCRM instance.
1. Navigate to **Admin** → **OAuth2 Clients and Tokens**.
1. Click **Create OAuth2 Client**.
1. In the **Redirect URI** field, enter the value from the **Redirect URI** field in Formie.
1. Copy the **Client ID** from SuiteCRM and paste in the **Client ID** field in Formie.
1. Copy the **Client Secret** from SuiteCRM and paste in the **Client Secret** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to SuiteCRM, where you must approve Formie to access your SuiteCRM account.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## vCita
Follow the below steps to connect to the vCita API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select vCita as the **Integration Provider**.

### Step 2. Connect to the vCita API
1. Go to <a href="https://www.vcita.com/" target="_blank">vCita</a> and login to your account.
1. In the left-hand sidebar menu, click on **Settings**.
1. Click on **Integrations**.
1. Find **Webhooks** and click on the **Connect** button.
1. Copy the **App Token** from vCita and paste in the **App Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Xero
Follow the below steps to connect to the Xero API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Xero as the **Integration Provider**.

### Step 2. Connect to the Xero API
1. Go to <a href="https://developer.xero.com/" target="_blank">Xero Developer Portal</a> and login to your account.
1. Navigate to **My Apps**.
1. Click the **New App** button.
1. Select **Web App**.
1. In the **Redirect URI** field, enter the value from the **Redirect URI** field in Formie.
1. Navigate to **Configuration**.
1. Click the **Generate a Secret** button.
1. Copy the **Client ID** from Xero and paste in the **Client ID** field in Formie.
1. Copy the **Client Secret** from Xero and paste in the **Client Secret** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to Xero, where you must approve Formie to access your Xero account.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Zoho
Follow the below steps to connect to the Zoho API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select Zoho as the **Integration Provider**.

### Step 2. Connect to the Zoho API
1. Go to <a href="https://accounts.zoho.com/developerconsole" target="_blank">Zoho API Console</a> and login to your account.
1. Click the **Add Client** button.
1. Click **Server-based Applications**.
1. In the **Authorized Redirect URIs** field, enter the value from the **Redirect URI** field in Formie.
1. Copy the **Client ID** from Zoho and paste in the **Client ID** field in Formie.
1. Copy the **Client Secret** from Zoho and paste in the **Client Secret** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to Zoho, where you must approve Formie to access your Zoho account.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.



