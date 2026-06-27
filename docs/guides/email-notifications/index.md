# Email Notifications

Deliverability, templates, attachments, and notification patterns.


##### [Attaching extra assets to Email Notifications](/guides/email-notifications/attaching-extra-assets-to-email-notifications)

Formie can attach files to notification emails in several built-in ways. When those are not enough, hook into the mail event and attach your own files.

##### [Building an Email Notification template from scratch](/guides/email-notifications/building-an-email-notification-template-from-scratch)

Formie sends through Craft's mailer, so you can wrap notification content in the same branded email layout as the rest of your project.

##### [Create Email Notifications just using Twig](/guides/email-notifications/create-email-notifications-just-using-twig)

Formie exposes its services to Twig like any Craft plugin. You can create notifications programmatically — useful for front-end admin areas or one-off setup templates.

##### [How to keep Email Notifications out of your junk emails](/guides/email-notifications/how-to-keep-email-notifications-out-of-your-junk-emails)

Spam filters are good at separating legitimate mail from junk — but Formie notification settings can still work against you if they look like spoofed or misconfigured messages. These tips help your Formie emails arrive in the inbox.

##### [Multi-site notification content](/guides/email-notifications/multi-site-notification-content)

Multi-site forms often need notification emails in the right language with the right recipients — without duplicating every form. Formie supports per-site notification overrides in the builder, conditional notifications, and variable-driven single templates. This guide compares the patterns and recommends one for common setups.

##### [PDF attachments with Email Notifications](/guides/email-notifications/pdf-attachments-with-email-notifications)

Email notifications can attach a generated PDF — useful for invoices, application summaries, signed agreements, or printable copies of what the user submitted. Formie renders a Twig PDF template at send time and attaches the result. This walkthrough creates the template, links it to a notification, and covers layout options.
