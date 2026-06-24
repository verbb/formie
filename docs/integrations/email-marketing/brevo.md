# Brevo (Sendinblue)
Follow the below steps to connect to the Brevo (Sendinblue) API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select Brevo as the **Integration Provider**.

### Step 2. Connect to the Brevo API
1. Go to <a href="https://www.brevo.com/" target="_blank">Brevo</a> and login to your account.
1. In the top-right corner, click on your profile and select **SMTP & API**.
1. Click the **+ Create a new API Key** button, and give it a name.
1. Copy the **API Key** from Brevo and paste in the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.

### Dynamic list assignment

By default, contacts are added to the list selected in the integration’s **List** setting. You can override this per submission by mapping a form field to the Brevo **List** integration field. This is useful when a dropdown on the form should determine which Brevo list the contact is subscribed to.

The static **List** setting is still required and acts as the fallback when no list is mapped. You can map a single list ID, comma-separated list IDs, or multiple values from a checkbox field.
