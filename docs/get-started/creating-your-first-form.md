# Creating Your First Form

You'll create a contact form, show it on a page and check a submitted message. Start with Formie installed, permission to create forms, a Twig template you can edit and working email settings in Craft.

Create a form in Formie named Contact Form with the handle `contactForm`. In the [form builder](docs:forms/form-builder), add an Email Address field and a Multi-Line Text field for the message. Make the fields required so the first test can also check validation. Save the form.

Configure an [email notification](docs:forms/email-notifications) for an address you can read. Use a sender address permitted by your mail service; use the submitted email as Reply-To when you want to reply to the visitor. Include the submitted fields in the notification body and save it.

In the Twig template for your contact page, render the saved form:

```twig
{{ craft.formie.renderForm('contactForm') }}
```

Open the page in a visitor browser. Submit the empty form to check the required fields, then enter an email and a recognisable message and submit again. Check the success response, the saved submission in Formie and the notification in the recipient's inbox.

If the submission exists but the email is missing, inspect Craft's queue and mail settings. These are separate stages: storing a submission does not prove that an email reached the recipient. Once the complete path works, [Rendering Forms](docs:templates/rendering-forms) covers output options and customisation.
