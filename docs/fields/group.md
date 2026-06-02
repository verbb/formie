# Group

Use Group when several fields belong together and should be treated as one structured section.

Group is not repeatable. If the user needs to add the same set of fields multiple times, use Repeater instead. If you only need a visual break, use Section or Heading.

## Key settings

- **Nested fields** - Add the fields that belong inside the group.
- **Layout** - Arrange nested fields in rows and columns inside the group.
- **Labels and instructions** - Explain the purpose of the grouped section.
- **Conditions** - Show or hide the group based on other form values when needed.

## Submitted value

Group stores a structured object containing the nested field values. This keeps related values together for templates, summaries, exports and integrations.

For GraphQL mutations, Group fields accept an object containing the nested field handles. Query the form’s `formFields` and include `inputTypeName`, or see [Create Submissions](/graphql/create-submissions#group-fields).

## Theme config

The Group field can be targeted with the `group` theme config key.

See [Group Field theme config](/theming/theme-config#group-field) for the full list of field-specific theme tags.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        group: {
            nestedFieldRows: {
                attributes: {
                    class: 'my-group-rows',
                },
            },
            nestedFieldContainer: {
                attributes: {
                    class: 'my-group-container',
                },
            },
        },
    },
}) }}
```

Use theme config for wrapper and layout attribute changes. Use a template override only when the nested field structure itself needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Related fields

- Use [Repeater](/fields/repeater) when the grouped fields need to be entered multiple times.
- Use [Section](/fields/section) or [Heading](/fields/heading) for visual grouping without a structured submitted value.

