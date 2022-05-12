# Page
A page represents a collection of fields, grouped by rows. For single-page forms, there will always be a single page. Whenever you're dealing with a page in your template, you're actually working with a `Page` object.

## Attributes

Attribute | Description
--- | ---
`id` | ID of the page.
`name` | The name of the page.
`sortOrder` | The order of the page.
`settings` | [Settings](#page-settings) for the page.


## Methods

Method | Description
--- | ---
`getRows()` | Returns an array of [Row](docs:developers/row) objects for this page.
`getCustomFields()` | Returns an array of [Field](docs:developers/field) objects for this page.


## Page Settings

### Attributes

Attribute | Description
--- | ---
`submitButtonLabel` | The label for the submit button.
`backButtonLabel` | The label for the back button, for multi-page forms.
`showBackButton` | Whether to show the back button, for multi-page forms.
`buttonsPosition` | How to position the submit button(s). Valid values are `left`, `right`, `center`, `left-right`.