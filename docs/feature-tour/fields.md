# Fields
Fields are arguably the core of a form, providing users the means to input actual content into a form. With Formie's form builder interface, fields are organised into rows. Each row can have multiple fields (up to 4), allowing fields to be shown in a column layout, side-by-side.

Each field varies by its type, allowing for different functionality and behaviour depending on the field.

<img src="https://verbb.io/uploads/plugins/formie/formie-address.png" />

:::tip
Looking for custom fields? Developers can create their own custom fields to extend the functionality of Formie. Read the [Custom Field](docs:developers/custom-field) docs for more.
:::

# Settings
All fields have a standard collection of settings.

Attribute | Description
--- | ---
Label | The label that describes this field.
Handle | How you’ll refer to this field in your templates.
Required | Whether this field should be required when filling out the form.
Error Message | When validating the form, show this message if an error occurs.
Label Position | How the label for the field should be positioned.
Instructions | Instructions to guide the user when filling out this form.
Instructions Position | How the instructions for the field should be positioned.
CSS Classes | Add classes to be outputted on this field’s container.
Container Attributes | Add attributes to be outputted on this field’s container.
Input Atributes | Add attributes to be outputted on this field’s input.

See the full [Field](docs:developers/field) documentation for more.

In addition some fields have some additional specific settings, described below.

# Field Types
Formie provides 26 different fields for use in your forms.

## Address
A field for addresses. There are a number of sub-fields that can be enabled as required:

- Address 1
- Address 2
- Address 3
- City
- State / Province
- ZIP / Postcode
- Country

## Agree
A field for a single checkbox. Its ideal purpose is to be an agreement checkbox for terms & conditions, or similar. It can be marked as required or not as well as have its checked and unchecked values set.

### Settings
Setting | Description
--- | ---
Description | The description for the field. This will be shown next to the checkbox.
Checked Value | The value of this field when it is checked.
Unchecked Value | The value of this field when it is unchecked.
Default Value | The default value for the field when it loads.

## Categories
A field for users to select categories from a dropdown field.

### Settings
Setting | Description
--- | ---
Placeholder | The option shown initially, when no option is selected.
Source | Which source do you want to select categories from?
Branch Limit | Limit the number of selectable category branches.

## Checkboxes
A field for a collection of checkboxes for the user to pick one or many options, each with their own label and value.

### Settings
Setting | Description
--- | ---
Options | Define the available options for users to select from.
Layout | Select which layout to use for these fields.

## Date/Time
A field to select the date or time, or both. There are some different display types:

- Calendar
- Dropdown fields (a field for year, month, etc)
- Text input fields (a field for year, month, etc)

### Settings
Setting | Description
--- | ---
Include Time | Whether this field should include the time.
Default Value | Entering a default value will place the value in the field when it loads.
Display Type | Set different display layouts for this field.

## Dropdown
A field for users select from a dropdown field. The field can also get to to allow multiple options to be set.

### Settings
Setting | Description
--- | ---
Placeholder | The option shown initially, when no option is selected.
Allow Multiple | Whether this field should allow multiple options to be selected.
Options | Define the available options for users to select from.

## Email Address
A field for users to enter their email. This is `<input type="email">` field.

### Settings
Setting | Description
--- | ---
Placeholder | The text that will be shown if the field doesn’t have a value.
Default Value | Entering a default value will place the value in the field when it loads.

## Entries
A field for users to select entries from a dropdown field.

### Settings
Setting | Description
--- | ---
Placeholder | The option shown initially, when no option is selected.
Sources | Which sources do you want to select entries from?
Limit | Limit the number of selectable entries.

## File Upload
A field for users to upload images from their device. This is `<input type="file">` field. It provides the following additional settings:

### Settings
Setting | Description
--- | ---
Upload Location | 
Limit Number of Files | Limit the number of files a user can upload.
Limit File Size | Limit the size of the files a user can upload.
Restrict allowed file types | 

## Group
A field to allow grouping of additional fields, in much the same way a row is grouped, by placing fields into columns. Grouped fields can have up to 4 fields in columns.

## Heading
A field to show text in a heading.

### Settings
Setting | Description
--- | ---
Heading Size | Choose the size for the heading.

## Hidden
A field to create a hidden input. This is `<input type="hidden">` field.

### Settings
Setting | Description
--- | ---
Default Value | |Entering a default value will place the value in the field when it loads.

## Html
A field to allow any HTML code to be shown on the form. Useful for `<iframe>` embeds, or any arbitrary HTML.

### Settings
Setting | Description
--- | ---
HTML Content | Enter HTML content to be rendered for this field.

## Multi-Line Text
A field for text entry that runs over multiple lines. This is a `<textarea>` input.

### Settings
Setting | Description
--- | ---
Placeholder | The text that will be shown if the field doesn’t have a value.
Default Value | Entering a default value will place the value in the field when it loads.
Limit Field Content | Whether to limit the content of this field.
Limit | Enter the number of characters or words to limit this field by.

## Name
A field for users to enter the name. Can be used as a single `<input type="text">` input, or split into several sub-fields:

- Prefix
- First Name
- Middle Name
- Last Name

### Settings
Setting | Description
--- | ---
Use Multiple Name Fields | Whether this field should use multiple fields for users to enter their details.

## Number
A field to enter a validated number. This is a `<input type="number">` field.

### Settings
Setting | Description
--- | ---
Placeholder | The text that will be shown if the field doesn’t have a value.
Default Value | Entering a default value will place the value in the field when it loads.
Limit Numbers | Whether to limit the numbers for this field.
Decimal Points | Set the number of decimal points to format the field value.

## Phone
A field to enter a phone number. This is a `<input type="tel">` field.

### Settings
Setting | Description
--- | ---
Show Country Code Dropdown | Whether to show an additional dropdown for selecting the country code.

## Products
A field for users to select products from a dropdown field.

### Settings
Setting | Description
--- | ---
Placeholder | The option shown initially, when no option is selected.
Sources | Which sources do you want to select products from?
Limit | Limit the number of selectable products.

## Radio
A field for radio button groups, for the user to pick a single option from.

### Settings
Setting | Description
--- | ---
Options | Define the available options for users to select from.
Layout | Select which layout to use for these fields.

## Repeater
A field to allow multiple sub-fields (similar to Group), but they are repeatable. Users can generate new rows of inputs as required. Sub-fields can be laid out in a similar fashion to rows, by placing fields into columns.

### Settings
Setting | Description
--- | ---
Add Label | The label for the button that adds another instance.
Minimum instances | The minimum required number of instances of this repeater's fields that must be completed.
Maximum instances | The maximum required number of instances of this repeater's fields that must be completed.

## Section
A UI element to split field content with a `<hr>` element.

### Settings
Setting | Description
--- | ---
Border | Add a border to this section.
Border Width | Set the border width (in pixels).
Border Color | Set the border color.

## Single-Line Text
A field for the user to enter text. This is a `<input type="text">` field.

### Settings
Setting | Description
--- | ---
Placeholder | The text that will be shown if the field doesn’t have a value.
Default Value | Entering a default value will place the value in the field when it loads.
Limit Field Content | Whether to limit the content of this field.
Limit | Enter the number of characters or words to limit this field by.

## Table
A field showing values in a tabular format. Similar to a Repeater field, users can add more rows of content, but is more simplistic than a Repeater field.

### Settings
Setting | Description
--- | ---
Table Columns | Define the columns your table should have.
Default Values | Define the default values for the field.
Add Row Label | The label for the button that adds another row.
Static | Whether this field should disallow adding more rows, showing only the default rows.
Minimum instances | The minimum required number of rows in this table that must be completed.
Maximum instances | The maximum required number of rows in this table that must be completed.

## Tags
A field for users to select or create tag elements.

### Settings
Setting | Description
--- | ---
Placeholder | The option shown initially, when no option is selected.
Source | Which source do you want to select tags from?

## Users
A field for users to select users from a dropdown field.

### Settings
Setting | Description
--- | ---
Placeholder | The option shown initially, when no option is selected.
Sources | Which sources do you want to select users from?
Limit | Limit the number of selectable users.

## Variants
A field for users to select variants from a dropdown field.

### Settings
Setting | Description
--- | ---
Placeholder | The option shown initially, when no option is selected.
Source | Which source do you want to select variants from?
Limit | Limit the number of selectable variants.
