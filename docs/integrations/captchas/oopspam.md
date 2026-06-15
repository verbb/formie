# OOPSpam

Follow the below steps to connect Formie to the OOPSpam API.

### Step 1. Open the Captcha Settings
1. Navigate to **Formie** → **Settings** → **Spam Protection** → **Captchas**.
1. Select **OOPSpam** in the left-hand sidebar.
1. Turn on **Enabled**.

### Step 2. Get your OOPSpam API Key
1. Go to [OOPSpam](https://www.oopspam.com/) and create an account, or sign in to an existing one.
1. Open your OOPSpam dashboard.
1. Copy the API key shown under **Your API key** on the main dashboard page.

### Step 3. Connect Formie to OOPSpam
1. Paste the API key into the **API Key** field in Formie.
1. Choose a **Spam Threshold**. Formie defaults to `3`, which matches OOPSpam's recommended starting point.
1. Save the captcha settings.

### Step 4. Form Setting
1. Go to the form you want to protect.
1. Enable **OOPSpam** for that form.
1. For multi-page forms, turn on **Show on All Pages** if the spam check should run on every page instead of only the final submit step.
1. Save the form.

OOPSpam screens submissions in the background, so there is no visible challenge on the front end.
