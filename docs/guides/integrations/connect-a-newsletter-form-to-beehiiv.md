# Connect a Newsletter Form to Beehiiv

A newsletter signup form lets visitors join your mailing list directly from your site. In this guide, you’ll build a form that adds a subscriber to a Beehiiv publication when they choose to receive your newsletter, then test both the signup and the opt-in condition.

You need Formie installed in your Craft site, permission to edit forms and integrations, and access to the site’s templates directory. You also need a Beehiiv account with a publication and access to its API key. Follow the [Beehiiv connection steps](/integrations/email-marketing/beehiiv) to create the integration, save its credentials and refresh the available publications before continuing.

Connecting the account lets Formie access Beehiiv. You’ll then enable that connection on your form and map the visitor’s email address to the subscriber’s email address in Beehiiv. Mapping tells Formie which answer belongs in each destination field.

## Create a Newsletter Form

In **Formie → Forms**, create a form named **Newsletter Signup**, with handle `newsletterSignup`. The handle is the name you’ll use to display the form in a template. Add a required Email Address field with handle `emailAddress`, and an Agree field named **Send Me the Newsletter**. Leave the Agree field optional so you can test submissions with and without consent. Save the form.

The Agree field lets the visitor choose whether the integration should run. It is separate from any confirmation email or subscription-state rules in your mailing platform.

## Map the Email Address

Open the form’s **Integrations** tab and select your Beehiiv integration. Enable it and choose the publication that should receive sign-ups from the **List** setting. In its mapping, connect the required **Email** destination to your form’s **Email Address** field using the variable picker. Do not type the field handle as plain text: a literal value would be sent unchanged for every submission.

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

If Formie saved the submission but the subscriber is missing, first check the integration condition and selected publication. Then check the Craft queue for a pending or failed job. Queued work needs a worker that runs without someone visiting the control panel; see [Troubleshooting](/get-started/troubleshooting).

A rejected payload usually needs a mapping correction or a destination-specific required value. Compare the saved answers with the mapped destination fields. Avoid repeating a send until you know whether the provider already accepted the first request, especially for messages, payments or workflows that create duplicate records.

After fixing the cause, use [Re-run Failed Integrations](/guides/integrations/re-run-failed-integrations-from-the-control-panel) where appropriate, then check the destination again. Remove test contacts and records when you finish.

## Apply the Same Checks to Other Providers

A CRM may need a contact, organisation or deal; an automation may need a webhook payload; a messaging integration needs a recipient or channel. Use the selected provider’s page for its actual settings. In each case, verify credentials, map the required values, submit through the real form and inspect the resulting record or message. A successful **Refresh** or **Connect** action covers only the account connection.
