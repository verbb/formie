# Messaging
Messaging integrations in Formie allow you to send real-time alerts and notifications to platforms like Slack, Telegram, or SMS services such as Twilio and Plivo.

Formie uses Craft‘s Queue system to send data to third-party providers. When a submission is successful a job is added to the queue so that it can be processed asynchronously.

You can create Messaging integrations by going to **Formie** → **Settings** → **Messaging**. You can pick from a list of providers Formie supports, and provide details for connecting to their respective APIs. You can also create multiple Messaging integrations, in case you need to connect to multiple, different providers.

## Supported Providers
Formie integrates with the following providers:
- Discord
- Plivo
- Slack
- Telegram
- Twilio

:::tip
Is your provider not in the list above? [Contact us](https://verbb.io/contact) to submit your interest, or look at the [Custom Integration](docs:developers/custom-integration) docs to write your own provider support.
:::

## Discord
Follow the below steps to connect to the Discord API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Messaging**.
1. Click the **New Integration** button.
1. Select **Discord** as the **Integration Provider**.

### Step 2. Create a Webhook in Discord
1. Open your Discord server.
1. Go to the **Channel Settings** where you want to post messages.
1. Click **Integrations** → **Webhooks**.
1. Click **New Webhook**, give it a name and select the target channel.
1. Copy the **Webhook URL**.

### Step 3. Form Settings
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Plivo
Follow the below steps to connect to the Plivo API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Messaging**.
1. Click the **New Integration** button.
1. Select **Plivo** as the **Integration Provider**.

### Step 2. Connect to the Plivo API
1. Go to the <a href="https://console.plivo.com" target="_blank">Plivo Console</a> and log in.
1. Copy the **Auth ID** from Plivo and paste in the **Auth ID** field in Formie.
1. Copy the **Auth Token** from Plivo and paste in the **Auth Token** field in Formie.
1. Navigate to **Phone Numbers**.
1. Select a valid number from your account.
1. Paste this into the **From Number** field in Formie.

### Step 3. Test the Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar to verify the connection.

### Step 4. Form Settings
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Slack
Follow the below steps to connect to the Slack API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Messaging**.
1. Click the **New Integration** button.
1. Select **Slack** as the **Integration Provider**.

### Step 2. Connect to the Slack API
1. Go to the <a href="https://api.slack.com/apps?new_app=1" target="_blank">Slack App Center</a>.
1. Create a new app, by entering an **App Name** and **Development Slack Workspace**.
1. In the left-hand sidebar, under **Settings**, click **Basic Information**.
1. Under the **App Credentials** section, copy the **Client ID** and **Client Secret** values and paste into the fields in Formie.
1. In the left-hand sidebar, under **Features**, click **OAuth & Permissions**.
1. In the section **Redirect URLs**, click the **Add New Redirect URL** button and enter the value from the **Redirect URI** field in Formie.
1. Then click the **Add** button, then click the **Save URLs** button.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.
1. You‘ll be redirected to Slack, where you must approve Formie to access your Slack account.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.


## Telegram
Follow the below steps to connect to the Telegram API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Messaging**.
1. Click the **New Integration** button.
1. Select **Telegram** as the **Integration Provider**.

### Step 2. Create a Telegram Bot
1. Open Telegram and search for **@BotFather**.
1. Start a chat and send the command `/newbot`.
1. Follow the prompts to give your bot a name and username.
1. Copy the **Bot Token** provided after creation.
1. Paste this value into the **Bot Token** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar to verify the connection.

### Step 4. Form Settings
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.

### Step 5. Get Your Chat ID
1. Start a conversation with your bot, or add it to a group or channel.
1. Send a message in the chat where you'd like the bot to post (this triggers a "message update").
1. Visit the following URL in your browser (replace the token): `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates`
1. Look for a `chat` object in the response. It will look something like: `"chat": { "id": -1001234567890, "type": "channel", "title": "Announcements" }`
1. Copy the `id` value and paste it into the **Chat ID** field when setting up the form integration.

> 💡 You can use a chat ID from a user, group, or channel — Telegram will route the message based on the ID automatically.


## Twilio
Follow the below steps to connect to the Twilio API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Messaging**.
1. Click the **New Integration** button.
1. Select **Twilio** as the **Integration Provider**.

### Step 2. Connect to the Twilio API
1. Go to the <a href="https://www.twilio.com/console" target="_blank">Twilio Console</a> and log in.
1. Copy the **Account SID** from Twilio and paste in the **Account SID** field in Formie.
1. Copy the **Auth Token** from Twilio and paste in the **Auth Token** field in Formie.
1. Navigate to **Phone Numbers** → **Manage** → **Active Numbers**.
1. Select a Twilio phone number you own.
1. Paste this into the **From Number** field in Formie.

### Step 3. Test the Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar to verify the connection.

### Step 4. Form Settings
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.
