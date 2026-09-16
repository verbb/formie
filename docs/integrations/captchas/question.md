# Question

Follow the below steps to set up Formie's built-in Question captcha.

### Step 1. Open the Captcha Settings
1. Navigate to **Formie** → **Settings** → **Spam Protection** → **Captchas**.
1. Select **Question** in the left-hand sidebar.
1. Turn on **Enabled**.

### Step 2. Add Your Security Questions
1. In **Security Questions**, add one or more questions.
1. For each question, enter one or more accepted answers in **Answers (comma-separated)**.
1. Save the captcha settings.

### Step 3. Form Setting
1. Go to the form you want to protect.
1. Enable **Question** for that form.
1. For multi-page forms, turn on **Show on All Pages** if the question should appear on every page instead of only the final submit step.
1. Save the form.

Formie picks one configured question at random. Answer matching is forgiving about case and punctuation, so there is no need to add every capitalization variant.

## Verify a Submission

Save the form, open it on your site and submit recognisable test values. Submit through the site and check the resulting submission and spam state. A saved credential alone does not verify the visitor-facing challenge or server-side check.

If nothing arrives, check whether integration conditions matched, whether the submission was complete and non-spam, and whether Craft’s queue has processed the job. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Connect and Test an Integration](/integrations/connect-and-test-an-integration) for a complete mapping and verification workflow.
