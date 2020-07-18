# Form

A Form object is the collection of fields, pages, rows and settings for a form. Whenever you're dealing with a form in your template, you're actually working with a `Form` object.

## Attributes

Attribute | Description
--- | ---
`id` | ID of the form.
`title` | The title of the form.
`handle` | The handle of the form.
`fieldContentTable` | The name of the content table that stores the content for this form's submissions.
`template` | Returns the [Form Template](), if provided.
`templateId` | The [Form Template]() ID for this form, if applicable.
`submitActionEntryId` | If chosen to have a entry be the redirect action for a form, this will be the [Entry]() ID.
`defaultStatus` | The default [Status]() for the submission made on this form.
`defaultStatusId` | The default [Status]() ID for the submission made on this form.

## Methods

Method | Description
--- | ---
`getFormFieldLayout()` | Returns the field layout for this form.
`getFormFieldContext()` | Returns the field context fields in this form use when being saved.
`getCpEditUrl()` | Returns the URL to the control panel to edit a form.
`getFormConfig()` | Returns configuration for a form, used in the form builder.
`getPages()` | Returns an array of [Page]() objects for the form.
`hasMultiplePages()` | Whether the form has more than 1 page.
`getCurrentPage()` | For multi-page forms, returns the current page the user is submitting on.
`getPreviousPage()` | For multi-page forms, returns the previous page, if applicable.
`getNextPage()` | For multi-page forms, returns the next page, if applicable.
`getCurrentPageIndex()` | For multi-page forms, returns the current page's index. Useful for progress bars.
`setCurrentPage()` | For multi-page forms, this sets the current page to the provided page.
`resetCurrentPage()` | For multi-page forms, this resets the current page. Usually used when the form is completed.
`isLastPage()` | For multi-page forms, whether the current page is the last page.
`isFirstPage()` | For multi-page forms, whether the current page is the first page.
`getCurrentSubmission()` | Returns the current submission, particularly useful for multi-page forms.
`resetCurrentSubmission()` | Resets the current submission. Usually used when the form is completed.
`getFields()` | Returns all [Field]() objects for this form.
`getFieldByHandle()` | Returns the [Field]() object for a provided handle.
`getNotifications()` | Returns all [Notification]() objects for this form.
`getEnabledNotifications()` | Returns all enabled [Notification]() objects for this form.
`getRedirectUrl()` | Returns the URL for the redirection upon final submission of the form.
`getRedirectEntry()` | Returns the [Entry]() used for redirection, if applicable.
`getCaptchas()` | Returns all enabled [Captcha]() objects for this form.
