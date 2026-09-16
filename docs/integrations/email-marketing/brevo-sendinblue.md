# Brevo (Sendinblue)
Connect Brevo (Sendinblue) to send newsletter sign-ups to your chosen mailing list. You need access to configure the form in Craft and credentials for the destination account. The steps below establish the connection; finish by sending a test submission to verify the result.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Email Marketing**.
1. Click the **New Integration** button.
1. Select Brevo as the **Integration Provider**.

### Step 2. Connect to the Brevo API
1. Go to <a href="https://www.brevo.com/" target="_blank">Brevo</a> and login to your account.
1. In the top-right corner, click on your profile and select **SMTP & API**.
1. Click the **+ Create a new API Key** button, and give it a name.
1. Copy the **API Key** from Brevo and paste in the **API Key** field in Formie.

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

### Dynamic List Assignment

By default, contacts are added to the list selected in the integration’s **List** setting. You can override this per submission by mapping a form field to the Brevo **List** integration field. This is useful when a dropdown on the form should determine which Brevo list the contact is subscribed to.

The static **List** setting is still required and acts as the fallback when no list is mapped. You can map a single list ID, comma-separated list IDs, or multiple values from a checkbox field.

## Verify a Submission

Save the form, open it on your site and submit recognisable test values. Look up the test email in the selected list and check its mapped values and subscription state. If the provider requires confirmation, complete that step before expecting an active subscription.

If nothing arrives, check whether integration conditions matched, whether the submission was complete and non-spam, and whether Craft’s queue has processed the job. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Connect and Test an Integration](/integrations/connect-and-test-an-integration) for a complete mapping and verification workflow.
