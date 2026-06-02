# Form

A Form object represents the whole form: its settings, pages, fields, notifications and render context. When you work with a form in Twig or PHP, this is usually the main object you are dealing with.

## Properties

Property | Description
--- | ---
`id` | The form ID.
`title` | The form title.
`handle` | The form handle.
`configJson` | Encoded JSON used by Formie’s front-end form handling.
`defaultStatus` | The default status for new submissions on this form.
`template` | The form template assigned to the form, if one is set.

## Methods

Method | Description
--- | ---
`getPages()` | Returns the form’s [Page](/reference/page) objects.
`hasMultiplePages()` | Returns whether the form has more than one page.
`getCurrentPage()` | Returns the current page for multi-page form handling.
`getPreviousPage()` | Returns the previous page, if one exists.
`getNextPage()` | Returns the next page, if one exists.
`getCurrentPageIndex()` | Returns the current page’s zero-based index.
`isFirstPage()` | Returns whether the current page is the first page.
`isLastPage()` | Returns whether the current page is the last page.
`getFields()` | Returns all [Field](/reference/field) objects on the form.
`getFieldByHandle()` | Returns a field by its handle.
`getNotifications()` | Returns all [Notification](/reference/notification) objects attached to the form.
`getEnabledNotifications()` | Returns the enabled notifications attached to the form.
`getRedirectUrl()` | Returns the URL Formie will redirect to after a successful submission, when applicable.
`getCurrentSubmission()` | Returns the submission currently associated with the form render, when available.
