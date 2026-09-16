# Mailchimp
Connect Mailchimp to send newsletter sign-ups to your chosen mailing list. You need access to configure the form in Craft and credentials for the destination account. The steps below establish the connection; finish by sending a test submission to verify the result.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select Mailchimp as the **Integration Provider**.

### Step 2. Connect to the Mailchimp API
1. Go to <a href="http://mailchimp.com/" target="_blank">Mailchimp</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Account**.
1. Click on **Extras** → **API keys**.
1. Under the **Your API keys** section, click the **Create A Key** button.
1. Copy the newly created key into the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Form Setting

1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Select the destination list.
1. Map its required email field to your form’s **Email Address** field.
1. Map any additional fields your list requires using the variable picker.
1. If you collect a newsletter opt-in, configure integration conditions so only visitors who choose it are sent.
1. Enable the integration.
1. Click **Save** to save the form.

### Additional Features
- Supports [Double Opt-in](https://mailchimp.com/help/about-double-opt-in/).
- Supports [Tags](https://mailchimp.com/help/manage-tags/).
- Supports [Groups](https://mailchimp.com/help/getting-started-with-groups/).
- Supports [GDPR Fields](https://mailchimp.com/help/collect-consent-with-gdpr-forms/).

## Verify a Submission

Save the form, open it on your site and submit recognisable test values. Look up the test email in the selected list and check its mapped values and subscription state. If the provider requires confirmation, complete that step before expecting an active subscription.

If nothing arrives, check whether integration conditions matched, whether the submission was complete and non-spam, and whether Craft’s queue has processed the job. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Connect and Test an Integration](/integrations/connect-and-test-an-integration) for a complete mapping and verification workflow.
