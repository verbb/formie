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

## Address
Setting | Description
--- | ---
`address1Label` | The label for the Address 1 sub-field.
`address1Placeholder` | The placeholder for the Address 1 sub-field.
`address1DefaultValue` | The default value for the Address 1 sub-field.
`address1Required` | Whether the Address 1 sub-field should be required.
`address1ErrorMessage` | The error message for the Address 1 sub-field.
`address1Collapsed` | Whether the Address 1 sub-field is collapsed in the control panel.
`address1Enabled` | The error message for the Address 1 sub-field.

`address2Label` | The label for the Address 2 sub-field.
`address2Placeholder` | The placeholder for the Address 2 sub-field.
`address2DefaultValue` | The default value for the Address 2 sub-field.
`address2Required` | Whether the Address 2 sub-field should be required.
`address2ErrorMessage` | The error message for the Address 2 sub-field.
`address2Collapsed` | Whether the Address 2 sub-field is collapsed in the control panel.
`address2Enabled` | The error message for the Address 2 sub-field.

`address3Label` | The label for the Address 3 sub-field.
`address3Placeholder` | The placeholder for the Address 3 sub-field.
`address3DefaultValue` | The default value for the Address 3 sub-field.
`address3Required` | Whether the Address 3 sub-field should be required.
`address3ErrorMessage` | The error message for the Address 3 sub-field.
`address3Collapsed` | Whether the Address 3 sub-field is collapsed in the control panel.
`address3Enabled` | The error message for the Address 3 sub-field.

`cityLabel` | The label for the City sub-field.
`cityPlaceholder` | The placeholder for the City sub-field.
`cityDefaultValue` | The default value for the City sub-field.
`cityRequired` | Whether the City sub-field should be required.
`cityErrorMessage` | The error message for the City sub-field.
`cityCollapsed` | Whether the City sub-field is collapsed in the control panel.
`cityEnabled` | The error message for the City sub-field.

`stateLabel` | The label for the State sub-field.
`statePlaceholder` | The placeholder for the State sub-field.
`stateDefaultValue` | The default value for the State sub-field.
`stateRequired` | Whether the State sub-field should be required.
`stateErrorMessage` | The error message for the State sub-field.
`stateCollapsed` | Whether the State sub-field is collapsed in the control panel.
`stateEnabled` | The error message for the State sub-field.

`zipLabel` | The label for the Zip sub-field.
`zipPlaceholder` | The placeholder for the Zip sub-field.
`zipDefaultValue` | The default value for the Zip sub-field.
`zipRequired` | Whether the Zip sub-field should be required.
`zipErrorMessage` | The error message for the Zip sub-field.
`zipCollapsed` | Whether the Zip sub-field is collapsed in the control panel.
`zipEnabled` | The error message for the Zip sub-field.

`countryLabel` | The label for the Country sub-field.
`countryPlaceholder` | The placeholder for the Country sub-field.
`countryDefaultValue` | The default value for the Country sub-field.
`countryRequired` | Whether the Country sub-field should be required.
`countryErrorMessage` | The error message for the Country sub-field.
`countryCollapsed` | Whether the Country sub-field is collapsed in the control panel.
`countryEnabled` | The error message for the Country sub-field.


## Agree
Setting | Description
--- | ---
`description` | The description for the field. This will be shown next to the checkbox.
`checkedValue `| The value of this field when it is checked.
`uncheckedValue` | The value of this field when it is unchecked.
`defaultValue` | The default value for the field when it loads.


## Categories
Setting | Description
--- | ---
Placeholder | The option shown initially, when no option is selected.
Source | Which source do you want to select categories from?
Branch Limit | Limit the number of selectable category branches.


## Checkboxes
Setting | Description
--- | ---
Options | Define the available options for users to select from.
Layout | Select which layout to use for these fields.


## Date/Time
Setting | Description
--- | ---
Include Time | Whether this field should include the time.
Default Value | Entering a default value will place the value in the field when it loads.
Display Type | Set different display layouts for this field.


## Dropdown
Setting | Description
--- | ---
Placeholder | The option shown initially, when no option is selected.
Allow Multiple | Whether this field should allow multiple options to be selected.
Options | Define the available options for users to select from.


## Email Address
Setting | Description
--- | ---
Placeholder | The text that will be shown if the field doesn’t have a value.
Default Value | Entering a default value will place the value in the field when it loads.


## Entries
Setting | Description
--- | ---
Placeholder | The option shown initially, when no option is selected.
Sources | Which sources do you want to select entries from?
Limit | Limit the number of selectable entries.


## File Upload
Setting | Description
--- | ---
Upload Location | 
Limit Number of Files | Limit the number of files a user can upload.
Limit File Size | Limit the size of the files a user can upload.
Restrict allowed file types | 

## Group


## Heading
Setting | Description
--- | ---
Heading Size | Choose the size for the heading.

## Hidden
Setting | Description
--- | ---
Default Value | |Entering a default value will place the value in the field when it loads.


## Html
Setting | Description
--- | ---
HTML Content | Enter HTML content to be rendered for this field.


## Multi-Line Text
Setting | Description
--- | ---
Placeholder | The text that will be shown if the field doesn’t have a value.
Default Value | Entering a default value will place the value in the field when it loads.
Limit Field Content | Whether to limit the content of this field.
Limit | Enter the number of characters or words to limit this field by.

## Name

- Prefix
- First Name
- Middle Name
- Last Name

### Settings
Setting | Description
--- | ---
`useMultipleFields` | Whether this field should use multiple fields for users to enter their details.

`prefixLabel` | The label for the Prefix sub-field.
`prefixPlaceholder` | The placeholder for the Prefix sub-field.
`prefixDefaultValue` | The default value for the Prefix sub-field.
`prefixRequired` | Whether the Prefix sub-field should be required.
`prefixErrorMessage` | The error message for the Prefix sub-field.
`prefixCollapsed` | Whether the Prefix sub-field is collapsed in the control panel.
`prefixEnabled` | The error message for the Prefix sub-field.

`firstNameLabel` | The label for the First Name sub-field.
`firstNamePlaceholder` | The placeholder for the First Name sub-field.
`firstNameDefaultValue` | The default value for the First Name sub-field.
`firstNameRequired` | Whether the First Name sub-field should be required.
`firstNameErrorMessage` | The error message for the First Name sub-field.
`firstNameCollapsed` | Whether the First Name sub-field is collapsed in the control panel.
`firstNameEnabled` | The error message for the First Name sub-field.

`middleNameLabel` | The label for the Middle Name sub-field.
`middleNamePlaceholder` | The placeholder for the Middle Name sub-field.
`middleNameDefaultValue` | The default value for the Middle Name sub-field.
`middleNameRequired` | Whether the Middle Name sub-field should be required.
`middleNameErrorMessage` | The error message for the Middle Name sub-field.
`middleNameCollapsed` | Whether the Middle Name sub-field is collapsed in the control panel.
`middleNameEnabled` | The error message for the Middle Name sub-field.

`lastNameLabel` | The label for the Last Name sub-field.
`lastNamePlaceholder` | The placeholder for the Last Name sub-field.
`lastNameDefaultValue` | The default value for the Last Name sub-field.
`lastNameRequired` | Whether the Last Name sub-field should be required.
`lastNameErrorMessage` | The error message for the Last Name sub-field.
`lastNameCollapsed` | Whether the Last Name sub-field is collapsed in the control panel.
`lastNameEnabled` | The error message for the Last Name sub-field.


## Number
Setting | Description
--- | ---
Placeholder | The text that will be shown if the field doesn’t have a value.
Default Value | Entering a default value will place the value in the field when it loads.
Limit Numbers | Whether to limit the numbers for this field.
Decimal Points | Set the number of decimal points to format the field value.


## Phone
Setting | Description
--- | ---
Show Country Code Dropdown | Whether to show an additional dropdown for selecting the country code.


## Products
Setting | Description
--- | ---
Placeholder | The option shown initially, when no option is selected.
Sources | Which sources do you want to select products from?
Limit | Limit the number of selectable products.


## Radio
Setting | Description
--- | ---
Options | Define the available options for users to select from.
Layout | Select which layout to use for these fields.


## Repeater
Setting | Description
--- | ---
Add Label | The label for the button that adds another instance.
Minimum instances | The minimum required number of instances of this repeater's fields that must be completed.
Maximum instances | The maximum required number of instances of this repeater's fields that must be completed.


## Section
Setting | Description
--- | ---
Border | Add a border to this section.
Border Width | Set the border width (in pixels).
Border Color | Set the border color.


## Single-Line Text
Setting | Description
--- | ---
Placeholder | The text that will be shown if the field doesn’t have a value.
Default Value | Entering a default value will place the value in the field when it loads.
Limit Field Content | Whether to limit the content of this field.
Limit | Enter the number of characters or words to limit this field by.


## Table
Setting | Description
--- | ---
Table Columns | Define the columns your table should have.
Default Values | Define the default values for the field.
Add Row Label | The label for the button that adds another row.
Static | Whether this field should disallow adding more rows, showing only the default rows.
Minimum instances | The minimum required number of rows in this table that must be completed.
Maximum instances | The maximum required number of rows in this table that must be completed.


## Tags
Setting | Description
--- | ---
Placeholder | The option shown initially, when no option is selected.
Source | Which source do you want to select tags from?


## Users
Setting | Description
--- | ---
Placeholder | The option shown initially, when no option is selected.
Sources | Which sources do you want to select users from?
Limit | Limit the number of selectable users.


## Variants
Setting | Description
--- | ---
Placeholder | The option shown initially, when no option is selected.
Source | Which source do you want to select variants from?
Limit | Limit the number of selectable variants.
