# CleanTalk

Follow the below steps to connect Formie to the CleanTalk API.

### Step 1. Open the Captcha Settings
1. Navigate to **Formie** → **Settings** → **Spam Protection** → **Captchas**.
1. Select **CleanTalk** in the left-hand sidebar.
1. Turn on **Enabled**.

### Step 2. Get Your CleanTalk Access Key
1. Go to [CleanTalk](https://cleantalk.org/) and create an account, or sign in to an existing one.
1. Add your website in the CleanTalk dashboard if prompted.
1. Open your CleanTalk Control Panel at `https://cleantalk.org/my`.
1. Copy your **Access key**. In Formie this is entered as the **API Key**.

### Step 3. Connect Formie to CleanTalk
1. Paste the access key into the **API Key** field in Formie.
1. Save the captcha settings.

### Step 4. Form Setting
1. Go to the form you want to protect.
1. Enable **CleanTalk** for that form.
1. For multi-page forms, turn on **Show on All Pages** if the spam check should run on every page instead of only the final submit step.
1. Save the form.

CleanTalk screens submissions in the background, so there is no visible challenge on the front end.

## Verify a Submission

Save the form, open it on your site and submit recognisable test values. Submit through the site and check the resulting submission and spam state. A saved credential alone does not verify the visitor-facing challenge or server-side check.

If nothing arrives, check whether integration conditions matched, whether the submission was complete and non-spam, and whether Craft’s queue has processed the job. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Connect and Test an Integration](/integrations/connect-and-test-an-integration) for a complete mapping and verification workflow.
