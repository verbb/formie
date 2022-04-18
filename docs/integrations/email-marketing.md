# Email Marketing
Email Marketing integrations are one of the provided integrations with Formie, and are used to subscribe a user to a third-party email marketing provider. For instance, you might want to add someone to your newsletter when they submit a form.

Formie uses Craft‘s Queue system to send data to third-party providers. When a submission is successful a job is added to the queue so that it can be processed asynchronously.

<img src="https://verbb.io/uploads/plugins/formie/email-marketing.png" />

You can create Email Marketing integrations by going to **Formie** → **Settings** → **Email Marketing**. You can pick from a list of providers Formie supports, and provide details for connecting to their respective APIs. You can also create multiple Email Marketing integrations, in case you need to connect to multiple, different providers.

You can also test the connection to the APIs, to ensure that your site and Formie can communicate with the API.

Once created, enabled and connected, these integrations will be available to configure in your forms.

<img src="https://verbb.io/uploads/plugins/formie/email-marketing-form.png" />

## Opt-in Field
You can nominate a field in your form to enforce opt-in behaviour. This means that data will only be sent if the nominated field provides a “truthy” value. For instance, it‘s common to provide an Agree field for users to tick to say they want to sign up to your newsletter. In this case, you would add an Agree field to your form, and select that as the **Opt-in Field**.

## List
Formie will fetch all available lists for the provider, allowing you to pick from. As lists and their available fields are cached for performance, you can also refresh the available lists if they change.

## Field Mapping
Along with lists, Formie will also fetch any custom fields, or provider-specific fields for a particular email marketing provider. You can map which Formie fields should have their values connected to their third-party field counterpart. 

## Supported Providers
Formie integrates with the following providers:
- ActiveCampaign
- Adestra
- Autopilot
- AWeber
- Benchmark
- Campaign Monitor
- [Campaign Plugin](https://plugins.craftcms.com/campaign)
- Constant Contact
- ConvertKit
- Drip
- EmailOctopus
- GetResponse
- iContact
- Klaviyo
- Mailchimp
- MailerLite
- Moosend
- Omnisend
- Ontraport
- Sender
- Sendinblue

:::tip
Is your provider not in the list above? [Contact us](https://verbb.io/contact) to submit your interest, or look at the [Custom Integration](docs:developers/custom-integration) docs to write your own provider support.
:::

## ActiveCampaign
Follow the below steps to connect to the ActiveCampaign API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
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


## Adestra
Follow the below steps to connect to the Adestra API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select Adestra as the **Integration Provider**.

### Step 2. Connect to the Adestra API
1. Copy the **API Key** from Adestra and paste in the **API Key** field in Formie.
1. Copy the **Workspace ID** from Adestra and paste in the **Workspace ID** field in Formie.
1. Copy the **Core Table ID** from Adestra and paste in the **Core Table ID** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Autopilot
Follow the below steps to connect to the Autopilot API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select Autopilot as the **Integration Provider**.

### Step 2. Connect to the Autopilot API
1. Go to <a href="https://www.autopilothq.com/" target="_blank">Autopilot</a> and login to your account.
1. In the left-hand sidebar menu, click on **Settings**.
1. In the left-hand sidebar sub-menu, click on **Autopilot API**.
1. Copy the **API Key** from Autopilot and paste in the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## AWeber
Follow the below steps to connect to the AWeber API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select AWeber as the **Integration Provider**.

### Step 2. Connect to the AWeber API
1. Go to <a href="https://labs.aweber.com/" target="_blank">AWeber Developer Center</a> and create a developer account.
1. In the top main menu, click on **My Apps**.
1. Click the **Create A New App** button.
1. Fill in the required fields.
    - For **Client Type** select **Confidential**.
1. In the **OAuth Redirect URL** field, enter the value from the **Redirect URI** field in Formie.
1. Copy the **Client ID** from AWeber and paste in the **Client ID** field in Formie.
1. Copy the **Client Secret** from AWeber and paste in the **Client Secret** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to AWeber, where you must approve Formie to access your AWeber account.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Benchmark
Follow the below steps to connect to the Benchmark API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select Benchmark as the **Integration Provider**.

### Step 2. Connect to the Benchmark API
1. Go to <a href="https://www.benchmarkemail.com/" target="_blank">Benchmark</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Integrations**.
1. In the left-hand sidebar menu, click on **API Key**.
1. Copy the **API Key** from Benchmark and paste in the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Campaign Monitor
Follow the below steps to connect to the Campaign Monitor API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select Campaign Monitor as the **Integration Provider**.

### Step 2. Connect to the Campaign Monitor API
1. Go to <a href="http://campaignmonitor.com/" target="_blank">Campaign Monitor</a> and login to your account.
1. In the top-right menu, click on your profile and select **Account Settings**.
1. Click on **API Keys**.
1. Click the **Show API Key**.
1. Copy the **API Key** from Campaign Monitor and paste it into the **API Key** field in Formie.
1. Copy the **Client ID** from Campaign Monitor and paste it into the **Client ID** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Campaign Plugin
The [Campaign Plugin](https://plugins.craftcms.com/campaign) requires no setup or settings, other than having the plugin installed and active.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select Campaign as the **Integration Provider**.

### Step 2. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Constant Contact
Follow the below steps to connect to the Constant Contact API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select Constant Contact as the **Integration Provider**.

### Step 2. Connect to the Constant Contact API
1. Go to the <a href="https://v3.developer.constantcontact.com/login/index.html" target="_blank">Constant Contact</a> application manager, and login to your account.
1. In the top main menu, click on **My Applications**.
1. Click on the **New Application** button at top-right.
1. Enter a name in the popup window and click **Save**.
1. In the **Redirect URI** field, enter the value from the **Redirect URI** field in Formie.
1. Copy the **API Key** from Constant Contact and paste in the **API Key** field in Formie.
1. Click the **Generate Secret** button, copy the **App Secret** and paste it into the **App Secret** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to Constant Contact, where you must approve Formie to access your Constant Contact account.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## ConvertKit
Follow the below steps to connect to the ConvertKit API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select ConvertKit as the **Integration Provider**.

### Step 2. Connect to the ConvertKit API
1. Go to <a href="https://convertkit.com/" target="_blank">ConvertKit</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Account Settings**.
1. Copy the **API Key** from ConvertKit and paste in the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Drip
Follow the below steps to connect to the Drip API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select Drip as the **Integration Provider**.

### Step 2. Connect to the Drip API
1. Go to <a href="https://www.getdrip.com/user/applications" target="_blank">Drip</a> and login to your account.
1. Click on **OAuth Applications**.
1. Enter a name for your application, and click the **Create Application** button.
1. In the **Callback URL** field, enter the value from the **Redirect URI** field In Formie.
1. Copy the **Client ID** from Drip and paste in the **Client ID** field in Formie.
1. Copy the **Client Secret** from Drip and paste in the **Client Secret** field in Formie.
1. Click the **Activate** button.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to Drip, where you must approve Formie to access your Drip account.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## EmailOctopus
Follow the below steps to connect to the EmailOctopus API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select EmailOctopus as the **Integration Provider**.

### Step 2. Connect to the Autopilot API
1. Go to <a href="https://emailoctopus.com/" target="_blank">EmailOctopus</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Integrations & API**.
1. Under the **EmailOctopus's API** section, click the **Create** button.
1. Copy the newly created key into the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## GetResponse
Follow the below steps to connect to the GetResponse API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select GetResponse as the **Integration Provider**.

### Step 2. Connect to the GetResponse API
1. Go to <a href="https://www.getresponse.com/" target="_blank">GetResponse</a> and login to your account.
1. Click on the **Menu** dropdown on the top-left of the screen, and select **Integrations and API**.
1. Click **API** in the menu.
1. Click the **Generate API Key** button.
1. Copy the **API Key** from GetResponse and paste in the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## iContact
Follow the below steps to connect to the iContact API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select iContact as the **Integration Provider**.

### Step 2. Connect to the iContact API
1. Go to <a href="https://www.icontact.com/" target="_blank">iContact</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Settings & Billing**.
1. Click on **iContact Integrations**.
1. Find the **Custom API Integrations** item and click the **Create** button.
1. Copy the **Application ID (AppId)** from iContact and paste in the **Application ID** field in Formie.
1. Copy the **Username / Email Address** from iContact and paste in the **Username** field in Formie.
1. Copy the **Password** from iContact and paste in the **Password** field in Formie.
1. Copy the **Account ID** from iContact and paste in the **Account ID** field in Formie.
1. Copy the **Client Folder ID** from iContact and paste in the **Client Folder ID** field in Formie.

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
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
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


## Mailchimp
Follow the below steps to connect to the Mailchimp API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select Mailchimp as the **Integration Provider**.

### Step 2. Connect to the Mailchimp API
1. Go to <a href="http://mailchimp.com/" target="_blank">Mailchimp</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Account**.
1. Click on **Extras** → **API keys**.
1. Under the **Your API keys** section, click the **Create A Key** button.
1. Copy the newly created key into the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.

### Additional Features
- Supports [Double Opt-in](https://mailchimp.com/help/about-double-opt-in/).
- Supports [Tags](https://mailchimp.com/help/manage-tags/).
- Supports [Groups](https://mailchimp.com/help/getting-started-with-groups/).
- Supports [GDPR Fields](https://mailchimp.com/help/collect-consent-with-gdpr-forms/).


## MailerLite
Follow the below steps to connect to the MailerLite API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select MailerLite as the **Integration Provider**.

### Step 2. Connect to the MailerLite API
1. Go to <a href="https://mailerlite.com" target="_blank">MailerLite</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Integrations**.
1. Under the **MailerLite API** heading, find the **Developer API** item, and click the **Use** button.
1. Copy the **API Key** from MailerLite and paste in the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Moosend
Follow the below steps to connect to the Moosend API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select Moosend as the **Integration Provider**.

### Step 2. Connect to the Moosend API
1. Go to <a href="https://identity.moosend.com/login/" target="_blank">Moosend</a> and login to your account.
1. Click on your settings icon in the top-right of the screen, and select **API Key**.
1. Copy the **API Key** from Moosend and paste in the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Omnisend
Follow the below steps to connect to the Omnisend API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select Omnisend as the **Integration Provider**.

### Step 2. Connect to the Omnisend API
1. Go to <a href="https://app.omnisend.com/#/login/" target="_blank">Omnisend</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Store Settings**.
1. Click on **Integrations & API** → **API keys**.
1. Click the **Create API Key** button.
1. Copy the **API Key** from Omnisend and paste in the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Ontraport
Follow the below steps to connect to the Ontraport API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select Ontraport as the **Integration Provider**.

### Step 2. Connect to the Ontraport API
1. Go to <a href="https://app.ontraport.com/" target="_blank">Ontraport</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Administration**.
1. In the left-hand sidebar menu, click on **Integrations**.
1. Click on **Ontraport API Instructions and Key Manager**.
1. Click the **New API Key** button.
1. Select an owner, and check all the options.
1. Copy the **App ID** from Ontraport and paste in the **App ID** field in Formie.
1. Copy the **API Key** from Ontraport and paste in the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Sender
Follow the below steps to connect to the Sender API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select Sender as the **Integration Provider**.

### Step 2. Connect to the Sender API
1. Go to <a href="https://www.sender.net/" target="_blank">Sender</a> and login to your account.
1. In the left-hand menu, click **My Account** → **API**.
1. Click the **Generate** button.
1. Copy the **API Key** from Sender and paste in the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Sendinblue
Follow the below steps to connect to the Sendinblue API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select Sendinblue as the **Integration Provider**.

### Step 2. Connect to the Sendinblue API
1. Go to <a href="https://www.sendinblue.com/" target="_blank">Sendinblue</a> and login to your account.
1. In the top-right corner, click on your profile and select **SMTP & API**.
1. Click the **+ Create a new API Key** button, and give it a name.
1. Copy the **API Key** from Sendinblue and paste in the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.

