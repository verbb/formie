# Email Notifications

Email notifications let a form send email when a submission happens.

A single form can have multiple notifications, which means one form can often cover several email jobs without needing separate forms for each variation.

Typical notification patterns include sending a confirmation to the person who submitted the form, sending an internal copy to a team inbox, or sending different messages depending on the answers in the form.

When you are setting notifications up, most of the work comes down to four things:

- who should receive the email
- when the email should send
- what content should be included
- whether attachments or a PDF should be added

## Recipients

Recipients can be fixed, conditional, or both.

That means you can often keep one notification and branch the recipients based on form answers, instead of creating a separate notification for every small variation.

If the email should only send for certain submissions, use [Conditions](/forms/conditions).

## Email content

The content editor stays fairly simple on purpose, so the email is more likely to work across mail clients.

Use the [variable picker](/developers/reference-tokens) to insert field values, submission details, site and user values, summary selectors, and custom variables.

If you need more control over the outer email layout, move that concern into an email template rather than trying to make the content field do everything.

## Templates and PDFs

A notification can also use:

- an email template to wrap the message
- a PDF template when you want to attach a generated PDF

That keeps the notification focused on who gets the email and what it should contain, while the template controls presentation.

## Preview and test send

Use the preview tab when you want a quick check of the rendered output with sample content.

Use a test send when you want to check real delivery, styling, or mail-provider behavior before relying on the notification in production.

## Queue behavior

Formie can send notifications through Craft’s queue. That is usually the better choice because it keeps submission requests faster for the person filling out the form.

If email delivery feels slow or unreliable, check the queue and mailer setup before assuming the notification itself is wrong.
