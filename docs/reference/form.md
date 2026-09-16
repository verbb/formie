# Form

A Form object represents the whole form: its settings, pages, fields, notifications and render context. When you work with a form in Twig or PHP, this is usually the main object you are dealing with.

## Properties

::: reference
### `id`

**Type:** `int|null`

The form ID.
:::

::: reference
### `title`

**Type:** `string|null`

The form title.
:::

::: reference
### `handle`

**Type:** `string|null`

The form handle.
:::

::: reference
### `configJson`

Encoded JSON used by Formie’s front-end form handling.
:::

::: reference
### `defaultStatus`

**Type:** `verbb\formie\models\SubmissionStatus|null`

The default status for new submissions on this form.
:::

::: reference
### `template`

**Type:** `verbb\formie\models\FormTemplate|null`

The form template assigned to the form, if one is set.
:::

::: reference
### `groupId`

**Type:** `int|null`

The ID of the form group this form belongs to in the control panel, if any.
:::


## Methods

::: reference
### `getPages()`

**Returns:** `array`

Returns the form’s [Page](/reference/page) objects.
:::

::: reference
### `hasMultiplePages()`

**Returns:** `bool`

Returns whether the form has more than one page.
:::

::: reference
### `getCurrentPage()`

**Returns:** `verbb\formie\models\FieldLayoutPage|null`

Returns the current page for multi-page form handling.
:::

::: reference
### `getPreviousPage()`

**Returns:** `verbb\formie\models\FieldLayoutPage|null`

Returns the previous page, if one exists.
:::

::: reference
### `getNextPage()`

**Returns:** `verbb\formie\models\FieldLayoutPage|null`

Returns the next page, if one exists.
:::

::: reference
### `getCurrentPageIndex()`

**Returns:** `int`

Returns the current page’s zero-based index.
:::

::: reference
### `isFirstPage()`

**Returns:** `bool`

Returns whether the current page is the first page.
:::

::: reference
### `isLastPage()`

**Returns:** `bool`

Returns whether the current page is the last page.
:::

::: reference
### `getFields()`

**Returns:** `array`

Returns all [Field](/reference/field) objects on the form.
:::

::: reference
### `getFieldByHandle()`

**Returns:** `verbb\formie\base\FieldInterface|null`

Returns a field by its handle.
:::

::: reference
### `getNotifications()`

**Returns:** `array|null`

Returns all [Notification](/reference/notification) objects attached to the form.
:::

::: reference
### `getEnabledNotifications()`

**Returns:** `array`

Returns the enabled notifications attached to the form.
:::

::: reference
### `getRedirectUrl()`

**Returns:** `string`

Returns the URL Formie will redirect to after a successful submission, when applicable.
:::

::: reference
### `getCurrentSubmission()`

**Returns:** `verbb\formie\elements\Submission|null`

Returns the submission currently associated with the form render, when available.
:::
