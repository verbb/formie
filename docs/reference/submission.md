# Submission

A Submission object represents data that has been saved through a form. That can be a completed submission, a partially saved submission, or a submission that has been marked as spam.

## Properties

Property | Description
--- | ---
`id` | The submission ID.
`formId` | The form ID this submission belongs to.
`form` | The [Form](/reference/form) this submission belongs to.
`statusId` | The submission status ID.
`status` | The submission status handle.
`userId` | The user ID associated with the submission, when collected.
`user` | The Craft user associated with the submission, when collected.
`siteId` | The site ID this submission was made on.
`snapshot` | The saved render snapshot used for field settings and submission output.
`ipAddress` | The submitter IP address, when collected.
`isIncomplete` | Whether the submission is incomplete.
`isSpam` | Whether the submission has been marked as spam.
`spamReason` | The spam reason, when the submission is marked as spam.
`validateCurrentPageOnly` | Whether validation should only run for the current page.
`dateCreated` | The date the submission was created.

## Methods

Method | Description
--- | ---
`getForm()` | Returns the submission’s [Form](/reference/form).
`getPages()` | Returns the form pages for this submission.
`getRows()` | Returns the form rows for this submission.
`getFields()` | Returns the form fields for this submission.
`getFieldByHandle()` | Returns a field by handle.
`setFieldValue()` | Sets a field value on the submission.
`getFieldValue()` | Returns the normal field value for the supplied field key.
`getFieldValueAsString()` | Returns the field value as a string.
`getFieldValueAsArray()` | Returns the field value as an array.
`getFieldValueForReference()` | Returns the field value prepared for singular reference contexts.
`getFieldValueForReferenceBlock()` | Returns the field value prepared for reference-block rendering.
`getFieldValueForExport()` | Returns the field value prepared for export.
`getFieldValueForSummary()` | Returns the field value prepared for summary views.
`getFieldValueForIntegration()` | Returns the field value prepared for an integration field.
`getValuesAsString()` | Returns all submitted field values as strings.
`getValuesAsArray()` | Returns all submitted field values as arrays.
`getValuesForExport()` | Returns all submitted field values prepared for export.
`getValuesForSummary()` | Returns all submitted field values prepared for summary views.
`getRelations()` | Returns element relations found in the submission.
`getPayments()` | Returns payment records associated with the submission.
`getSubscriptions()` | Returns subscription records associated with the submission.

`getFieldValueForEmail()` and `getFieldValueForVariable()` still exist as deprecated compatibility aliases. New code should use `getFieldValueForReferenceBlock()` and `getFieldValueForReference()`.
