# Overview

Integrations connect your forms to other services and to Craft elements. You can send newsletter sign-ups to a mailing platform, create contacts in a CRM, notify a messaging channel or create an entry from a submission. Other integrations provide features within the form itself, such as address suggestions, spam protection and payments.

Choose a provider from the categories in this section for its account requirements and setup instructions. The settings and checks depend on what the integration does.

## Connect a Provider

For integrations that require an account, start in **Formie → Settings** and open the relevant integration category. Create an integration, select its provider and enter the credentials or connect the account as described on the provider’s page. Give the integration a name that identifies the account or purpose, especially if you use more than one connection to the same service.

These settings belong to the connection and can be reused by multiple forms. A successful **Refresh** or **Connect** action confirms access to the provider; it does not prove that a form’s values will be accepted or delivered to the intended destination.

## Configure an Individual Form

For integrations that send submission data, open the form’s **Integrations** tab, select the connection and enable it. Choose the destination, such as a mailing list, CRM object or messaging channel, then configure any required values. Save the form when you finish.

Where field mapping is available, it connects answers in your form to fields in the receiving system. For example, map your form’s Email Address field to the provider’s email field using the variable picker. Typing a field handle as plain text sends that literal text instead of the visitor’s answer. Map the destination’s required fields before adding optional values.

Use integration conditions when the action should depend on an answer. A newsletter integration might run only when a visitor selects an opt-in field. The form can still complete when that condition is not met; the condition controls whether that integration runs.

Address providers, CAPTCHAs and payment integrations have their own field or form settings and verification steps. Follow their provider pages for those workflows.

## Verify the Result

For integrations that send submission data, submit the form through your site with recognisable test values. Check the saved submission in **Formie → Submissions**, then inspect the resulting record or message in the destination. Confirm that the values arrived in the correct account, list or channel. If you added a condition, also submit values that should not trigger the integration and confirm that no action occurs.

If the result is missing, check the form’s integration settings, required mappings and conditions. For queued integrations, check whether Craft’s queue has processed the job; the [troubleshooting guide](/get-started/troubleshooting) explains queue setup. A saved submission and a successful account connection are separate from successful delivery.

For a complete worked example, follow [Connect a Newsletter Form to Beehiiv](/guides/integrations/connect-a-newsletter-form-to-beehiiv). The guide takes you through creating the form, mapping its email field, adding an opt-in condition and checking the resulting subscriber.
