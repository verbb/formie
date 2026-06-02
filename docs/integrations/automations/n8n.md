# n8n
Follow the below steps to connect to the n8n API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Automations**.
1. Click the **New Integration** button.
1. Select **n8n** as the **Integration Provider**.

### Step 2. Connect to the n8n API
1. Go to the <a href="https://n8n.cloud/" target="_blank">n8n Dashboard</a> or your self-hosted instance.
1. Create a new workflow from scratch.
1. Add a **Webhook** trigger node to your canvas.
1. Set the HTTP Method to **POST**.
1. Click **Save**, then copy the **Test URL** or **Production URL** from the Webhook node.

### Step 3. Form Setting & Test Payload
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.
1. Click on the **Send Test Payload** button to send dummy content to the URL.
