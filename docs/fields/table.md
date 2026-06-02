# Table

Use Table when the form needs row-and-column input rather than one value at a time.

Use Table for simple grids such as line items, availability rows, or repeated measurements. Use Repeater when each repeated item needs nested fields or a richer layout.

## Key settings

- **Columns** - Define the column labels and input types for each row.
- **Default rows** - Provide initial row values.
- **Add row label** - Customize the button label used to add another row.
- **Add and remove rows** - Allow or prevent users from changing row count.
- **Static** - Show only the configured default rows and prevent users from adding more.
- **Minimum and maximum rows** - Keep the submitted table within a useful range.
- **Column input types** - Choose suitable controls for each column, such as text, number, checkbox, date or select.

## Column types

Table columns can use these field types:

- Checkbox
- Color
- Date
- Dropdown
- Email
- Heading
- Multi-line Text
- Number
- Time
- Single-line Text
- URL

## Submitted value

Table stores structured row data. Treat the value as a list of rows with column values, not as a single text value.

For GraphQL mutations, Table fields use generated input types based on the table columns. Query the form’s `formFields` and include `inputTypeName` when building mutations.

## Theme config

The Table field can be targeted with the `table` theme config key.

See [Table Field theme config](/theming/theme-config#table-field) for the full list of field-specific theme tags.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        table: {
            fieldTable: {
                attributes: {
                    class: 'my-table',
                },
            },
            fieldAddButton: {
                attributes: {
                    class: 'my-table-add',
                },
            },
            fieldRemoveButton: {
                attributes: {
                    class: 'my-table-remove',
                },
            },
        },
    },
}) }}
```

Table also exposes input-specific theme tags such as `tableSinglelineInput`, `tableNumberInput`, `tableSelectInput`, `tableCheckboxInput`, `tableDateInput`, `tableTimeInput` and others.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Front-end reference

Table relies on Formie’s front-end JavaScript when users can add or remove rows. If you override templates, preserve the row controls and input names needed to keep row data aligned.

The front-end docs live on the separate browser UI reference site and cover rendered markup, data attributes, styling classes and JavaScript behavior for custom front-end implementations.

- [Table](/browser/ui-reference/fields/table)

## Related fields

- Use [Repeater](/fields/repeater) when each repeated item needs nested fields or richer layout.
- Use [Group](/fields/group) when the structured set appears only once.

