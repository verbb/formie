# Friendly Captcha

Follow the below steps to connect Formie to Friendly Captcha.

### Step 1. Open the Captcha Settings
1. Navigate to **Formie** → **Settings** → **Spam Protection** → **Captchas**.
1. Select **Friendly Captcha** in the left-hand sidebar.
1. Turn on **Enabled**.

### Step 2. Get your Friendly Captcha Keys
1. Go to [Friendly Captcha](https://friendlycaptcha.com/) and create an account, or sign in to an existing one.
1. In the dashboard, go to **Applications** and create a new application.
1. Copy the **Sitekey** shown for the application.
1. Go to **API Keys** and create an API key.
1. Copy the API key and store it somewhere safe, because Friendly Captcha only shows it once.

### Step 3. Connect Formie to Friendly Captcha
1. In Formie, choose **API Version**.
1. Select **v2** for new setups using a Friendly Captcha sitekey and API key.
1. Select **v1 (legacy)** only if you are already using Friendly Captcha v1 and have an existing site key and secret key.
1. Paste the **Site Key** into the **Site Key** field.
1. For **v2**, paste your Friendly Captcha API key into the second key field.
1. For **v1**, paste your legacy Friendly Captcha secret key into the second key field.
1. Optionally choose a **Language** and **Start Mode**.
1. Save the captcha settings.

### Step 4. Form Setting
1. Go to the form you want to protect.
1. Enable **Friendly Captcha** for that form.
1. For multi-page forms, turn on **Show on All Pages** if the captcha should run on every page instead of only the final submit step.
1. Save the form.

Friendly Captcha relies on Formie's front-end output, so test it on the rendered form after saving your settings.
