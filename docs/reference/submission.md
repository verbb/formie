# Submission

A Submission object represents data that has been saved through a form. That can be a completed submission, a partially saved submission, or a submission that has been marked as spam.

## Properties

::: reference
### `id`

**Type:** `int|null`

The submission ID.
:::

::: reference
### `formId`

**Type:** `int|null`

The form ID this submission belongs to.
:::

::: reference
### `form`

**Type:** `verbb\formie\elements\Form|null`

The [Form](/reference/form) this submission belongs to.
:::

::: reference
### `statusId`

**Type:** `int|null`

The submission status ID.
:::

::: reference
### `status`

**Type:** `string|null`

The submission status handle.
:::

::: reference
### `userId`

**Type:** `int|null`

The user ID associated with the submission, when collected.
:::

::: reference
### `user`

**Type:** `craft\elements\User|null`

The Craft user associated with the submission, when collected.
:::

::: reference
### `siteId`

**Type:** `int|null`

The site ID this submission was made on.
:::

::: reference
### `snapshot`

**Type:** `array`

The saved render snapshot used for field settings and submission output.
:::

::: reference
### `ipAddress`

**Type:** `string|null`

The submitter IP address, when collected.
:::

::: reference
### `isIncomplete`

**Type:** `bool`

Whether the submission is incomplete.
:::

::: reference
### `isSpam`

**Type:** `bool`

Whether the submission has been marked as spam.
:::

::: reference
### `spamReason`

**Type:** `string|null`

The spam reason, when the submission is marked as spam.
:::

::: reference
### `validateCurrentPageOnly`

**Type:** `bool|null`

Whether validation should only run for the current page.
:::

::: reference
### `dateCreated`

**Type:** `DateTime|null`

The date the submission was created.
:::


## Methods

::: reference
### `getForm()`

**Returns:** `verbb\formie\elements\Form|null`

Returns the submission’s [Form](/reference/form).
:::

::: reference
### `getPages()`

**Returns:** `array`

Returns the form pages for this submission.
:::

::: reference
### `getRows()`

**Returns:** `array`

Returns the form rows for this submission.
:::

::: reference
### `getFields()`

**Returns:** `array`

Returns the form fields for this submission.
:::

::: reference
### `getFieldByHandle()`

**Returns:** `verbb\formie\base\FieldInterface|null`

Returns a field by handle.
:::

::: reference
### `setFieldValue()`

**Returns:** `void`

Sets a field value on the submission.
:::

::: reference
### `getFieldValue()`

**Returns:** `mixed`

Returns the normal field value for the supplied field key.
:::

::: reference
### `getFieldValueAsString()`

**Returns:** `mixed`

Returns the field value as a string.
:::

::: reference
### `getFieldValueAsArray()`

**Returns:** `mixed`

Returns the field value as an array.
:::

::: reference
### `getFieldValueForReference()`

**Returns:** `mixed`

Returns the field value prepared for singular reference contexts.
:::

::: reference
### `getFieldValueForReferenceBlock()`

**Returns:** `mixed`

Returns the field value prepared for reference-block rendering.
:::

::: reference
### `getFieldValueForExport()`

**Returns:** `mixed`

Returns the field value prepared for export.
:::

::: reference
### `getFieldValueForSummary()`

**Returns:** `mixed`

Returns the field value prepared for summary views.
:::

::: reference
### `getFieldValueForIntegration()`

**Returns:** `mixed`

Returns the field value prepared for an integration field.
:::

::: reference
### `getValuesAsString()`

**Returns:** `array`

Returns all submitted field values as strings.
:::

::: reference
### `getValuesAsArray()`

**Returns:** `array`

Returns all submitted field values as arrays.
:::

::: reference
### `getValuesForExport()`

**Returns:** `array`

Returns all submitted field values prepared for export.
:::

::: reference
### `getValuesForSummary()`

**Returns:** `array`

Returns all submitted field values prepared for summary views.
:::

::: reference
### `getRelations()`

**Returns:** `array`

Returns element relations found in the submission.
:::

::: reference
### `getPayments()`

**Returns:** `array|null`

Returns payment records associated with the submission.
:::

::: reference
### `getSubscriptions()`

**Returns:** `array|null`

Returns subscription records associated with the submission.
:::
