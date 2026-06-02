# LiveChat
Follow the below steps to connect to the LiveChat API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Help Desk**.
1. Click the **New Integration** button.
1. Select **LiveChat** as the **Integration Provider**.

### Step 2. Connect to the LiveChat API
1. Go to <a href="https://developers.livechat.com/console/" target="_blank">LiveChat Developer Console</a> and log in.
1. Navigate to **Apps**.
1. Click the **Build App** button, and fill out the app name and description.
1. Proceed to **Configure a widget** using the default settings.
1. Proceed to **Configure Authorization**.
1. In the **Redirect URI whitelist** field, enter the value from the **Redirect URI** field in Formie.
1. Copy the **Client ID** from LiveChat and paste in the **Client ID** field in Formie.
1. Copy the **Client Secret** from LiveChat and paste in the **Client Secret** field in Formie.
1. Set the required scopes (such as `tickets.write` and `tickets.read`).
1. Go to <a href="https://my.livechatinc.com" target="_blank">LiveChat</a>.
1. Navigate to **Settings** → **Chat Page**.
1. Copy _just_ the number in the URL from LiveChat and paste in the **License ID** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Connect** button in the right-hand sidebar.

### Step 4. Form Settings
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.
