# SharpSpring
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
