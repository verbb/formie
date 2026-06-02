# Page

A Page object represents one page in a form. Single-page forms still have a page object; multi-page forms just make the page sequence more visible.

## Properties

Property | Description
--- | ---
`label` | The page label.
`handle` | The page handle, derived from the label.
`sortOrder` | The page order within the form.
`settings` | The page settings, including page button labels and layout options.

## Methods

Method | Description
--- | ---
`getRows()` | Returns the row objects on this page.
`getFields()` | Returns the [Field](/reference/field) objects on this page.
`getFieldByHandle()` | Returns a field on this page by its handle.
`isConditionallyHidden()` | Returns whether the page is hidden for a submission because of conditions.
`hasConditions()` | Returns whether the page has conditions configured.
`getConditions()` | Returns the page conditions.
`getClientConditions()` | Returns the conditions in the shape used by Formie’s front-end handling.
`getFieldErrors()` | Returns errors for fields on the page for a submission.
