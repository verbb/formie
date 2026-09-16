# Repeater

Use Repeater when the same group of fields may need to be entered more than once.

Use Repeater when each repeated item needs multiple fields or a richer layout. If the repeated data is a simple grid of values, Table may be easier.

## Collect Details for Several Attendees

Suppose one person can register up to five attendees. Add a Repeater field named **Attendees** to your registration form, then place Name and Email Address fields inside it. Each row represents one attendee, so the two answers stay together.

Set the minimum row count to one and the maximum to five. Use an add-button label such as **Add Attendee** so visitors know what another row represents. Save the form and open it on your site. Add two attendees, submit, and check that the saved submission contains two separate sets of names and email addresses. Remove a row before submitting again to check that only the remaining attendee is saved.

Choose Table instead if each item only needs simple columns; Repeater lets each item use nested Formie fields and their validation.

## Key Settings

- **Nested fields** - Define the fields that appear in each repeated row.
- **Minimum and maximum rows** - Control how few or how many rows can be submitted.
- **Default rows** - Show initial rows before the user adds anything.
- **Add and remove button labels** - Customise the row-management controls.
- **Layout** - Arrange the nested fields inside each repeated row.

## Submitted Value

Repeater stores a structured collection of rows. Each row contains the nested field values for that repeated item.

For GraphQL mutations, Repeater fields accept a generated input object with row data. Query the form’s `formFields` and include `inputTypeName`, or see [Create Submissions](/graphql/create-submissions#repeater-fields).

## Theme Config

The Repeater field can be targeted with the `repeater` theme config key.

See [Repeater Field theme config](/reference/theme-tag-reference#repeater-field) for the full list of field-specific theme tags.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        repeater: {
            nestedFieldRow: {
                attributes: {
                    class: 'my-repeater-row',
                },
            },
            fieldAddButton: {
                attributes: {
                    class: 'my-repeater-add',
                },
            },
            fieldRemoveButton: {
                attributes: {
                    class: 'my-repeater-remove',
                },
            },
        },
    },
}) }}
```

Use theme config for wrapper, row and button attribute changes. Use a template override only when the repeated row markup needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Front-End Reference

Repeater relies on Formie’s front-end JavaScript to add, remove and index rows correctly. If you override templates, preserve the row containers and controls Formie uses for those actions.

The front-end docs live on the separate browser UI reference site and cover rendered markup, data attributes, styling classes and JavaScript behaviour for custom front-end implementations.

- [Repeater](https://docs.verbb.io/formie/browser/ui-reference/fields/repeater)

## Related Fields

- Use [Group](/fields/group) when the nested fields appear only once.
- Use [Table](/fields/table) when each repeated item is a simple row of column values.

