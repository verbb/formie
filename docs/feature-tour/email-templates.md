# Email Templates
Email templates allow you to create custom templates for the emails that Formie sends out. These can be used similar to a "wrapper" around the content body of the email. Each [Email Notification](docs:feature-tour/email-notifications), can be assigned an email template, so you can have multiple email templates for a variety of different requirements.

For example, you might have a "Customer" email template, which is a branded template, that's been rigorously browser tested, and is sent to customers or users of your site. Another "Admin" email template might be for the internal staff of the website, receiving a users' submission content, which doesn't need to be anything other than informational. In this case, you would have an email template for each, with each pointing to their own Twig template file, as part of your project.

When creating a new email template, be sure to give it a name, handle and set the HTML template path.

:::tip
You'll notice there's a "Copy Templates" field. You can use this to copy the default template into the provided folder to get off to an even quicker start to customise the template.
:::

For more information about the actual templating for email templates, head to our [Email Templates](docs:template-guides/email-templates) templating guide.

