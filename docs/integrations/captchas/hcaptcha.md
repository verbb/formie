# hCaptcha

Follow the below steps to connect Formie to hCaptcha.

### Step 1. Open the Captcha Settings
1. Navigate to **Formie** → **Settings** → **Captchas**.
1. Select **hCaptcha** in the left-hand sidebar.
1. Turn on **Enabled**.

### Step 2. Get your hCaptcha Keys
1. Go to the [hCaptcha dashboard](https://dashboard.hcaptcha.com/) and sign in to your account.
1. Open the **Sites** tab and create a new sitekey.
1. Open the **Settings** tab and generate your secret key.
1. Copy the **Site Key** and **Secret Key**.

### Step 3. Connect Formie to hCaptcha
1. Paste the **hCaptcha Site Key** into the **hCaptcha Site Key** field in Formie.
1. Paste the **hCaptcha Secret Key** into the **hCaptcha Secret Key** field in Formie.
1. Choose the front-end options you want, such as **Theme**, **Size**, **Minimum Score**, **Language**, and **Script Loading Method**.
1. Save the captcha settings.

### Step 4. Form Setting
1. Go to the form you want to protect.
1. Enable **hCaptcha** for that form.
1. For multi-page forms, turn on **Show on All Pages** if the captcha should run on every page instead of only the final submit step.
1. Save the form.

hCaptcha supports visible and invisible modes. The **Size** setting in Formie controls which mode is rendered for the form.
