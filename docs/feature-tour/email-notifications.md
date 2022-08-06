# Email Notifications
Email notifications are an important part of any form, to both notify the user their submission has been received, and to notify admins of their submission, so they can action.

<img src="https://verbb.io/uploads/plugins/formie/formie-email-notification.png" />

Each notification is form-specific, and you can create as many notifications as required. There are a number of fields for a notification:

- Enabled - Whether the notification should be sent.
- Name - An internal name to call this notification in the control panel.
- Recipients - Define who should receive the email notification - set emails or conditions.
- Recipient Emails - A list of email addresses that this notification should go to.
- Recipient Conditions - Conditional logic to determine which email addresses receive the notification.
- Subject - The subject line for the email.
- Email Content - The full body of the email. See [Email Content](#email-content).
- From Name - Set the name attribute that the email is shown to have come from.
- From Email - The senders email address.
- Reply-To Email - The reply-to email address for the email.
- CC - The cc email address for the email.
- BCC - The bcc email address for the email.
- Attach File Uploads - Whether to attach any user-uploaded files to the email.
- Email Template - To select a custom [Email Template](docs:feature-tour/email-templates).
- Attach PDF Template - Whether to attach a PDF template to this email notification.
- PDF Template - To select a custom [PDF Template](docs:template-guides/pdf-templates).


:::warning
Clicking **Apply** in the notification modal won't immediately save the notification. You'll need to save the form. A small "Unsaved" badge will display next to a brand-new notification to warn you. However, you'll also be prompted to save the form if you try and navigate away without saving the form.
:::

## Email Content
The email content field is a rich-text field providing basic formatting functionality. Due to the complexities of email rendering, this is kept simple on purpose. If you wish to build custom templates for your emails, read the [Email Templates](docs:template-guides/email-templates) docs.

One feature of this field is the variable select field. This allows you to pull in dynamic content from Craft, or from the submission this email notification is made on. Commonly, you'll want to use the "All Fields" option to generate a full list of field's and their content, producing similar content to:

```
**First Name:**
Peter

**Last Name:**
Sherman

**Email**
psherman@wallaby.com

**Message**
Just wanted to say, I love the new website!
```

But other variables exists, such as (but not restricted to):

- User Information (if the submission is made by a logged-in user)
    - User ID
    - User Email
    - Username
    - User Full Name
    - User IP Address
- Time/Date
- System and site settings

Along with all available fields used in your form.

## Sending Emails
Formie uses Craft's Queue system to send out email notifications. The reason for this is performance. Often sending of emails can be slow, particularly for SMTP relays. This slowness can be a detrimental experience for your users, as they wait for the page to load after submitting a form. This is compounded by if multiple email notifications need to be sent out for a form submission. This can lead to user frustration, or even worse - navigating away and not bothering filling in your form.

For this reason, we highly recommend you read the following guides on ensuring your Craft install is [properly configured for email delivery](https://craftcms.com/guides/why-doesnt-craft-send-emails#setting-up-email), and your [runQueueAutomatically](https://craftcms.com/docs/4.x/config/config-settings.html#runqueueautomatically) config setting.

For further information about the best-practices with queues, we recommend reading [Robust queue job handling in Craft CMS](https://nystudio107.com/blog/robust-queue-job-handling-in-craft-cms).

## Email Preview
The content of an email notification can be previewed in the "Preview" tab, when editing a notification. This will use the settings you've defined in your notifications, and the field in your form to render a preview of how your email notification will look.

Dummy content will be generated for fields.

<img src="https://verbb.io/uploads/plugins/formie/formie-email-preview.png" />

## Send Test Email
Emails can be sent as a test to a nominated email. This will essentially send the content as shown in the Email Preview to the email address, and is considerably useful for testing email deliverability and other issues.

## Conditions
Email notifications can also have set conditions on whether they send or not. Through the conditions' builder, you can create complex rules for each of your email notifications.

<img src="https://verbb.io/uploads/plugins/formie/formie-notification-conditions.png" />

First, it's a matter of choosing whether you want these sets of rules to "Send" or "Not Send" the email notification. For example, you might like to only send an email notification if the users' email isn't for a number of domain names.

Then, you can set whether to match against "All" rules, or just "Any" rule.

Finally, building your conditions is a matter of specifying 3 important bits of information: "Field", "Condition", "Value". For "Field", pick the field you want to test a condition against. A "Condition" will be one of the following:

- `is`
- `is not`
- `greater than`
- `less than`
- `contains`
- `starts with`
- `ends with`

And provide a "Value" you wish to compare against. For fields that support set options (Dropdown, Radio, Checkboxes), you must pick from your list of defined options. Otherwise, text values are supported.
