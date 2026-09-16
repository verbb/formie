# Notification

A Notification object represents one email notification attached to a form. A form can have more than one notification.

## Properties

::: reference
### `id`

**Type:** `int|null`

The notification ID.
:::

::: reference
### `formId`

**Type:** `int|null`

The form ID this notification belongs to.
:::

::: reference
### `templateId`

**Type:** `int|null`

The email template ID, when one is selected.
:::

::: reference
### `pdfTemplateId`

**Type:** `int|null`

The PDF template ID, when one is selected.
:::

::: reference
### `name`

**Type:** `string|null`

The control panel name for the notification.
:::

::: reference
### `handle`

**Type:** `string|null`

The notification handle.
:::

::: reference
### `enabled`

**Type:** `bool|null`

Whether the notification is enabled.
:::

::: reference
### `subject`

**Type:** `string|null`

The email subject.
:::

::: reference
### `recipients`

**Type:** `string`

The recipient mode, such as `email` or `conditions`.
:::

::: reference
### `to`

**Type:** `string|null`

The email address or variable content used for the `To` recipient.
:::

::: reference
### `toConditions`

**Type:** `array|null`

Conditional recipient rules for the `To` recipient.
:::

::: reference
### `cc`

**Type:** `string|null`

The email address or variable content used for the `Cc` recipient.
:::

::: reference
### `bcc`

**Type:** `string|null`

The email address or variable content used for the `Bcc` recipient.
:::

::: reference
### `replyTo`

**Type:** `string|null`

The reply-to email address or variable content.
:::

::: reference
### `replyToName`

**Type:** `string|null`

The reply-to name or variable content.
:::

::: reference
### `from`

**Type:** `string|null`

The sender email address.
:::

::: reference
### `fromName`

**Type:** `string|null`

The sender name.
:::

::: reference
### `content`

**Type:** `string|null`

The raw notification content.
:::

::: reference
### `attachFiles`

**Type:** `bool|null`

Whether user-uploaded files should be attached.
:::

::: reference
### `attachPdf`

**Type:** `string|null`

Whether a PDF should be attached.
:::

::: reference
### `enableConditions`

**Type:** `bool|null`

Whether the notification has sending conditions enabled.
:::

::: reference
### `conditions`

**Type:** `array|null`

The conditions used to decide whether the notification should send.
:::


## Methods

::: reference
### `getParsedContent()`

**Returns:** `string`

Returns the notification content as rendered HTML.
:::

::: reference
### `getToEmail()`

**Returns:** `string|null`

Resolves the notification’s `To` recipient for a submission.
:::

::: reference
### `getStatusCondition()`

**Returns:** `string|null`

Returns the status condition for a submission, when applicable.
:::

::: reference
### `renderTemplate()`

**Returns:** `string`

Renders notification template content with supplied variables.
:::

::: reference
### `getTemplate()`

**Returns:** `verbb\formie\models\EmailTemplate|null`

Returns the selected email template model, if one is set.
:::

::: reference
### `getPdfTemplate()`

**Returns:** `verbb\formie\models\PdfTemplate|null`

Returns the selected PDF template model, if one is set.
:::

::: reference
### `getAssetAttachments()`

**Returns:** `array`

Returns asset attachments for the notification.
:::
