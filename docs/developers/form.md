# Form

A Form object is the collection of fields, pages, rows and settings for a form. Whenever you're dealing with a form in your template, you're actually working with a `Form` object.

## Attributes

Attribute | Description
--- | ---
`id` | ID of the form.
`title` | The title of the form.
`handle` | The handle of the form.
`fieldContentTable` | The name of the content table that stores the content for this form's submissions.
`template` | Returns the [Form Template](docs:feature-tour/form-templates), if provided.
`templateId` | The [Form Template](docs:feature-tour/form-templates) ID for this form, if applicable.
`submitActionEntryId` | If chosen to have a entry be the redirect action for a form, this will be the [Entry](https://docs.craftcms.com/api/v4/craft-elements-entry.html) ID.
`defaultStatus` | The default status for the submission made on this form.
`defaultStatusId` | The default status ID for the submission made on this form.
`formId` | A unique identifier for the form, in the format `fui-{id}`.
`configJson` | This will output encoded JSON to be output in the `<form>` element. This is required for JavaScript-enabled forms.

## Methods

Method | Description
--- | ---
`getFormFieldLayout()` | Returns the field layout for this form.
`getFormFieldContext()` | Returns the field context fields in this form use when being saved.
`getCpEditUrl()` | Returns the URL to the control panel to edit a form.
`getFormConfig()` | Returns configuration for a form, used in the form builder.
`getPages()` | Returns an array of [Page](docs:developers/page) objects for the form.
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
`getCustomFields()` | Returns all [Field](docs:developers/field) objects for this form.
`getFieldByHandle()` | Returns the [Field](docs:developers/field) object for a provided handle.
`getNotifications()` | Returns all [Notification](docs:developers/notification) objects for this form.
`getEnabledNotifications()` | Returns all enabled [Notification](docs:developers/notification) objects for this form.
`getRedirectUrl()` | Returns the URL for the redirection upon final submission of the form.
`getRedirectEntry()` | Returns the [Entry](https://docs.craftcms.com/api/v4/craft-elements-entry.html) used for redirection, if applicable.

## Form Settings
Each form has a collection of settings associated with it.

### Attributes

Attribute | Description
--- | ---
`displayFormTitle` | Whether to show the form’s title.
`displayPageTabs` | Whether to show the form’s page tabs.
`displayCurrentPageTitle` | Whether to show the form’s current page title.
`displayPageProgress` | Whether to show the form’s page progress.
`submitMethod` | The form’s submit method. `ajax` or `page-reload`.
`submitAction` | Set the submission action. `message`, `entry`, `url`, `reload`.
`submitActionTab` | Whether to the redirect URL should open in a new tab.
`submitActionUrl` | The URL to redirect to on success.
`submitActionFormHide` | Whether the form should be hidden on success.
`submitActionMessage` | The success message as HTML shown on validation success.
`submitActionMessageTimeout` | Whether the success message should hide after the provided number of milliseconds.
`submitActionMessagePosition` | The form’s submit message position.
`errorMessage` | The error message as HTML shown on validation failure.
`errorMessagePosition` | The form’s error message position.
`loadingIndicator` | The type of loading indicator to use. `spinner` or `text`.
`loadingIndicatorText` | The text for the loading indicator.
`validationOnSubmit` | Whether to validate the form fields on-submit.
`validationOnFocus` | Whether to validate the form fields on-focus.
`submissionTitleFormat` | The submission title format.
`collectIp` | Whether to collect the IP address of the user.
`collectUser` | Whether to collect a logged-in user against the form.
