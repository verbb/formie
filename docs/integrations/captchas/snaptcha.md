# Snaptcha

Follow the below steps to use Snaptcha with Formie.

### Step 1. Install and Configure Snaptcha
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

## Verify a Submission

Save the form, open it on your site and submit recognisable test values. Submit through the site and check the resulting submission and spam state. A saved credential alone does not verify the visitor-facing challenge or server-side check.

If nothing arrives, check whether integration conditions matched, whether the submission was complete and non-spam, and whether Craft’s queue has processed the job. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Connect and Test an Integration](/integrations/connect-and-test-an-integration) for a complete mapping and verification workflow.
