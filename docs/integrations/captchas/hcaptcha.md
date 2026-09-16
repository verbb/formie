# hCaptcha

Follow the below steps to connect Formie to hCaptcha.

### Step 1. Open the Captcha Settings
1. Navigate to **Formie** → **Settings** → **Spam Protection** → **Captchas**.
1. Select **hCaptcha** in the left-hand sidebar.
1. Turn on **Enabled**.

### Step 2. Get Your hCaptcha Keys
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

## Verify a Submission

Save the form, open it on your site and submit recognisable test values. Submit through the site and check the resulting submission and spam state. A saved credential alone does not verify the visitor-facing challenge or server-side check.

If nothing arrives, check whether integration conditions matched, whether the submission was complete and non-spam, and whether Craft’s queue has processed the job. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Connect and Test an Integration](/integrations/connect-and-test-an-integration) for a complete mapping and verification workflow.
