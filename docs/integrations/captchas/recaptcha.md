# reCAPTCHA

Follow the below steps to connect Formie to Google reCAPTCHA.

### Step 1. Open the Captcha Settings
1. Navigate to **Formie** → **Settings** → **Captchas**.
1. Select **reCAPTCHA** in the left-hand sidebar.
1. Turn on **Enabled**.

### Step 2. Create your reCAPTCHA Keys
1. Go to the [reCAPTCHA Admin console](https://www.google.com/recaptcha/admin) or the [Google Cloud reCAPTCHA page](https://console.cloud.google.com/security/recaptcha).
1. Create a new website key for the mode you want to use.
1. Use a **score-based** key for **reCAPTCHA v3**.
1. Use a **challenge** key for **reCAPTCHA v2 Checkbox**.
1. For **reCAPTCHA Enterprise**, create a website key whose type matches the Enterprise key type you want to use in Formie.
1. Add the domains where the form will run.
1. Save the key.
1. Copy the **Site Key**.
1. Open the key details and use **Use Legacy Key** to reveal the **legacy secret key** for third-party integrations such as Formie.
1. If you are using **reCAPTCHA Enterprise**, also note your Google Cloud **Project ID**.

### Step 3. Connect Formie to reCAPTCHA
1. Choose the **reCAPTCHA Type** in Formie.
1. Paste the **reCAPTCHA Site Key** into the **reCAPTCHA Site Key** field.
1. Paste the **legacy secret key** into the **reCAPTCHA Secret Key** field.
1. If you are using **reCAPTCHA Enterprise**, select the **Enterprise Key Type** that matches the key you created and enter the **Project ID**.
1. Optionally configure the other settings that apply to your chosen mode, such as **Minimum Score**, **Badge Type**, **Theme**, **Size**, **Action**, **Language**, and **Script Loading Method**.
1. Save the captcha settings.

### Step 4. Form Setting
1. Go to the form you want to protect.
1. Enable **reCAPTCHA** for that form.
1. For multi-page forms, turn on **Show on All Pages** if reCAPTCHA should run on every page instead of only the final submit step.
1. Save the form.

For most forms, **reCAPTCHA v3** is the best starting point. Use the checkbox or Enterprise challenge modes only when you need a visible challenge.
