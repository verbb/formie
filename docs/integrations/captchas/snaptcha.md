# Snaptcha

Follow the below steps to use Snaptcha with Formie.

### Step 1. Install and configure Snaptcha
1. Install the [Snaptcha plugin](https://plugins.craftcms.com/snaptcha) for Craft CMS.
1. Configure Snaptcha itself using that plugin's settings and setup instructions.

### Step 2. Open the Captcha Settings
1. Navigate to **Formie** → **Settings** → **Spam Protection** → **Captchas**.
1. Select **Snaptcha** in the left-hand sidebar.
1. Turn on **Enabled**.
1. Save the captcha settings.

### Step 3. Form Setting
1. Go to the form you want to protect.
1. Enable **Snaptcha** for that form.
1. For multi-page forms, turn on **Show on All Pages** if Snaptcha should run on every page instead of only the final submit step.
1. Save the form.

Formie reads the Snaptcha field name and token value from the Snaptcha plugin, so there are no extra keys to enter in Formie itself.
