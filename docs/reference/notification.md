# Notification

A Notification object represents one email notification attached to a form. A form can have more than one notification.

## Properties

Property | Description
--- | ---
`id` | The notification ID.
`formId` | The form ID this notification belongs to.
`templateId` | The email template ID, when one is selected.
`pdfTemplateId` | The PDF template ID, when one is selected.
`name` | The control panel name for the notification.
`handle` | The notification handle.
`enabled` | Whether the notification is enabled.
`subject` | The email subject.
`recipients` | The recipient mode, such as `email` or `conditions`.
`to` | The email address or variable content used for the `To` recipient.
`toConditions` | Conditional recipient rules for the `To` recipient.
`cc` | The email address or variable content used for the `Cc` recipient.
`bcc` | The email address or variable content used for the `Bcc` recipient.
`replyTo` | The reply-to email address or variable content.
`replyToName` | The reply-to name or variable content.
`from` | The sender email address.
`fromName` | The sender name.
`content` | The raw notification content.
`attachFiles` | Whether user-uploaded files should be attached.
`attachPdf` | Whether a PDF should be attached.
`enableConditions` | Whether the notification has sending conditions enabled.
`conditions` | The conditions used to decide whether the notification should send.

## Methods

Method | Description
--- | ---
`getParsedContent()` | Returns the notification content as rendered HTML.
`getToEmail()` | Resolves the notification’s `To` recipient for a submission.
`getStatusCondition()` | Returns the status condition for a submission, when applicable.
`renderTemplate()` | Renders notification template content with supplied variables.
`getTemplate()` | Returns the selected email template model, if one is set.
`getPdfTemplate()` | Returns the selected PDF template model, if one is set.
`getAssetAttachments()` | Returns asset attachments for the notification.
