# Telegram
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
