# Connect and Test an Integration

An integration sends a form submission to another system. Connecting an account gives Formie credentials; mapping tells it which answers to send. A test submission confirms that the receiving system accepts those values.

This walkthrough uses a newsletter form and Beehiiv. You need a Beehiiv account with a publication, access to its API key, and permission to edit Formie integrations and forms. Follow [Beehiiv](/integrations/email-marketing/beehiiv) to save the credentials and refresh the available publications before continuing.

## Create a Newsletter Form

Create a form named **Newsletter Signup**, with handle `newsletterSignup`. Add a required Email Address field with handle `emailAddress`, and an Agree field named **Send Me the Newsletter**. Save the form.

The Agree field lets the visitor choose whether the integration should run. It is separate from any confirmation email or subscription-state rules in your mailing platform.

## Map the Email Address

Open the form’s **Integrations** tab and select your Beehiiv integration. Enable it and choose the publication that should receive sign-ups. In its mapping, connect the required **Email** destination to your form’s **Email Address** field using the variable picker. Do not type the field handle as plain text: a literal value would be sent unchanged for every submission.

Beehiiv also exposes **Reactivate Existing** and **Send Welcome Email**. Choose those values deliberately. Reactivating a contact and sending a welcome email are separate decisions from copying the email address. Leave additional custom fields unmapped unless your workflow needs them or the destination requires them.

Configure the integration’s conditions so it runs only when **Send Me the Newsletter** is checked. Save the form.

## Submit Through the Site

Create `templates/newsletter.twig` in the Craft project:

```twig
{{ craft.formie.renderForm('newsletterSignup') }}
```

Open `/newsletter`, enter a test mailbox you control, select the newsletter option and submit. Check **Formie → Submissions** for the completed record, then open the chosen Beehiiv publication and find that email address. Confirm the address, custom fields you mapped and subscription state. If your selected settings send a welcome email, inspect that mailbox too.

Submit again with another test address and leave the newsletter option unchecked. The form should still follow its own validation and completion rules, but this integration should not add that address to the publication. This second test checks the condition rather than only the connection.

## Diagnose Missing or Incorrect Data

If Formie saved the enquiry but the subscriber is missing, first check the integration condition and selected publication. Then check the Craft queue for a pending or failed job. Queued work needs a worker that runs without someone visiting the control panel; see [Troubleshooting](/get-started/troubleshooting).

A rejected payload usually needs a mapping correction or a destination-specific required value. Compare the saved answers with the mapped destination fields. Avoid repeating a send until you know whether the provider already accepted the first request, especially for messages, payments or workflows that create duplicate records.

After fixing the cause, use [Re-run Failed Integrations](/guides/integrations/re-run-failed-integrations-from-the-control-panel) where appropriate, then check the destination again. Remove test contacts and records when you finish.

## Apply the Same Checks to Other Providers

A CRM may need a contact, organisation or deal; an automation may need a webhook payload; a messaging integration needs a recipient or channel. Use the selected provider’s page for its actual settings. In each case, verify credentials, map the required values, submit through the real form and inspect the resulting record or message. A successful **Refresh** or **Connect** action covers only the account connection.
