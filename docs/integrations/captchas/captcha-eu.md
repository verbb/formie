# Captcha.Eu

Follow the below steps to connect Formie to Captcha.eu.

### Step 1. Open the Captcha Settings
1. Navigate to **Formie** → **Settings** → **Spam Protection** → **Captchas**.
1. Select **Captcha.eu** in the left-hand sidebar.
1. Turn on **Enabled**.

### Step 2. Get Your Captcha.Eu Keys
1. Go to [Captcha.eu](https://www.captcha.eu/) and create an account, or sign in to an existing one.
1. In the dashboard, go to **Domains** and add the domain you want to protect.
1. Captcha.eu will generate a **Public Key** and **Rest Key** for that domain.
1. Copy both keys.

### Step 3. Connect Formie to Captcha.Eu
1. Paste the **Public Key** into the **Public Key** field in Formie.
1. Paste the **Rest Key** into the **Rest Key** field in Formie.
1. Leave **Endpoint** as `https://www.captcha.eu` unless Captcha.eu has given you a different endpoint.
1. Save the captcha settings.

### Step 4. Form Setting
1. Go to the form you want to protect.
1. Enable **Captcha.eu** for that form.
1. For multi-page forms, turn on **Show on All Pages** if the captcha should run on every page instead of only the final submit step.
1. Save the form.

Captcha.eu relies on Formie's front-end output, so test it on the rendered form after saving your settings.

## Verify a Submission

Save the form, open it on your site and submit recognisable test values. Submit through the site and check the resulting submission and spam state. A saved credential alone does not verify the visitor-facing challenge or server-side check.

If nothing arrives, check whether integration conditions matched, whether the submission was complete and non-spam, and whether Craft’s queue has processed the job. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Connect and Test an Integration](/integrations/connect-and-test-an-integration) for a complete mapping and verification workflow.
