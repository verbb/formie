# Field

A Field object represents a form field of a particular type. Each field has its own unique attributes and functionality. Whenever you're dealing with a field in your template, you're actually working with a `Field` object.

## Attributes

Attribute | Description
--- | ---
`id` | ID of the field.
`name` | The name of the field.
`label` | An alias to `name`.
`handle` | The handle of the field.
`type` | The type of the field.
`form` | The [Form](docs:developers/form) this field belongs to.
`formId` | The [Form](docs:developers/form) ID for the form this field belongs to.
`rowId` | The [Row](docs:developers/row) ID for the row this field belongs to.
`rowIndex` | The [Row](docs:developers/row) index for the row this field belongs to. This is used for field ordering.
`settings` | A collection of settings for the field. See [Field Settings](#field-settings).

## Methods

Method | Description
--- | ---
`getSvgIcon()` | Returns the contents of an SVG icon used for a field type.
`getSvgIconPath()` | Returns the path to the SVG icon used for a field type.
`getIsNew()` | Denotes whether this field is new.
`hasLabel()` | Whether the field has a label or not. Some fields do not have one.
`renderLabel()` | An alias to `hasLabel()`.
`getIsTextInput()` | Whether this field is classified as a text input.
`getIsSelect()` | Whether this field is classified as a select input.
`getIsFieldset()` | Whether this field contains a fieldset. Normally, for when fields have sub-fields.
`getExtraBaseFieldConfig()` | Returns any base-level configuration data for the field.
`getFieldDefaults()` | Returns any defaults for the field, when it's created.
`getContainerAttributes()` | Returns an array of options for container attributes.
`getInputAttributes()` | Returns an array of options for input attributes.
`getFrontEndInputHtml()` | Returns the HTML for a the front-end template for a field.
`getFrontEndInputOptions()` | Returns an object of variables that can be used for front-end fields.
`getEmailHtml()` | Returns the HTML for an email notification for a field.
`afterCreateField()` | A function called after a field has been created in the control panel.


# Field Settings
The settings for a field will differ per-type, but the following are general settings applicable to all fields.

Attribute | Description
--- | ---
`columnWidth` | How many columns (out of 12) this field takes up.
`labelPosition` | The position of the field's label.
`instructionsPosition` | The position of the field's instructions.
`cssClasses` | Any CSS classes, applied on the outer container of a field.
`containerAttributes` | A collection of attributes added to the outer container of a field.
`inputAttributes` | A collection of attributes added to the `input` element of a field - where applicable.

