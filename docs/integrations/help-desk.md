# Help Desk
Help Desk integrations in Formie let you automatically create support tickets from form submissions in platforms like Zendesk and Gorgias. This is ideal for contact, support, or issue-reporting forms that should route directly to your customer service team.

Formie uses Craft‘s Queue system to send data to third-party providers. When a submission is successful a job is added to the queue so that it can be processed asynchronously.

You can create Help Desk integrations by going to **Formie** → **Settings** → **Help Desk**. You can pick from a list of providers Formie supports, and provide details for connecting to their respective APIs. You can also create multiple Help Desk integrations, in case you need to connect to multiple, different providers.

## Supported Providers
Formie integrates with the following providers:
- Gorgias
- Zendesk

:::tip
Is your provider not in the list above? [Contact us](https://verbb.io/contact) to submit your interest, or look at the [Custom Integration](docs:developers/custom-integration) docs to write your own provider support.
:::

## Gorgias
Follow the below steps to connect to the Gorgias API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Help Desk**.
1. Click the **New Integration** button.
1. Select Gorgias as the **Integration Provider**.

### Step 2. Connect to the Gorgias API
1. Go to <a href="https://gorgias.com" target="_blank">Gorgias</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Settings**.
1. In the left-hand sidebar, click the **Settings** icon.
1. In the left-hand sidebar, click **REST API**.
1. Click the **Create API Key** button.
1. Copy the **Base API URL** from Gorgias and paste in the **API URL** field in Formie.
1. Copy the **Username** from Gorgias and paste in the **Username** field in Formie.
1. Copy the **Password** from Gorgias and paste in the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Zendesk
Follow the below steps to connect to the Zendesk API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Help Desk**.
1. Click the **New Integration** button.
1. Select Zendesk as the **Integration Provider**.

### Step 2. Connect to the Zendesk API
1. Go to <a href="https://www.zendesk.com" target="_blank">Zendesk</a> and log in to your account.
1. In the left-hand sidebar, click the **Settings** icon.
1. Click the **Go to Admin Center** link.
1. In the left-hand sidebar select **Apps and Integrations** → **Zendesk API**.
1. Ensure that **Token Access** is enabled.
1. Click the **Add API Token** button.
1. Copy the **API Key** from Zendesk and paste in the **API Key** field in Formie.
1. Enter the full domain (including `https://`) for your Zendesk account in the **Domain** field in Formie.
1. Enter your Zendesk **login email** (the same one used to generate the token) in the **Username** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar to verify the connection.

### Step 4. Form Settings
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.
