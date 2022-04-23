# PDF Templates
For any email notification, you can also attach a PDF file, rendered to your needs. You can manage multiple PDF templates, much like Email Templates.

Let's take an example template:

```twig
<h1>Thanks for your email enquiry. The below email was sent from {{ siteName }}.</h1>

{{ contentHtml }}
```

Here, we've created a new template - let's name this file `index.html`, and place this in the following path `templates/_pdf/index.html`.

Then, navigate to **Formie** → **Settings** → **PDF Templates** and select **New Template**. Give it an appropriate name, and enter `_pdf` in the **HTML Template** field. This is because we want to set the templates to a folder that contains all pdf-related templates.

Let's go ahead and edit an Email Notification for a form. If you haven't added one yet, go ahead and create a new one. Under the **Template** tab, enable the **Attach PDF Template** setting and select our desired PDF Template in the **PDF Template** setting.

A PDF will be rendered according to the Twig template you've provided, and attach to the email notification when it's being sent.

## Available Template Variables
Your templates have access to the following variables:

Variable | Description
--- | ---
`notification` | A [ Notification](docs:developers/notification) object, for current email notification.
`submission` | A [Submission](docs:developers/submission) object, for what the email is notifying about.
`form` | A [Form](docs:developers/form) object, for what the email is notifying about.
`contentHtml` | The HTML generated from the **Email Content** field for the email notification.