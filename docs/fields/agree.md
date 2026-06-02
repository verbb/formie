# Agree

Use Agree when the form needs one explicit yes/no acknowledgement, such as accepting terms, confirming consent, or agreeing to be contacted.

Use it for a single agreement checkbox. If the user should choose between multiple options, use Radio or Checkboxes instead.

## Key settings

- **Description** - Add the agreement text shown beside the checkbox. This can include links.
- **Checked value** - Set the value saved when the checkbox is selected.
- **Unchecked value** - Set the value saved when the checkbox is not selected.
- **Default value** - Choose whether the agreement is checked when the form first loads.
- **Required** - Require the acknowledgement before the form can be submitted.

## Submitted value

Agree stores the configured checked or unchecked value. Treat it as an explicit acknowledgement value rather than a general-purpose boolean unless your configured values are boolean-like.

When querying or saving submissions through GraphQL, the field handle is used as the field name. Query the form’s `formFields` and include `inputTypeName` if you need to confirm the generated input type for a specific form.

## Theme config

The Agree field can be targeted with the `agree` theme config key.

See [Agree Field theme config](/theming/theme-config#agree-field) for the full list of field-specific theme tags.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        agree: {
            fieldOptions: {
                attributes: {
                    class: 'my-agree-options',
                },
            },
            fieldOption: {
                attributes: {
                    class: 'my-agree-option',
                },
            },
            fieldInput: {
                attributes: {
                    class: 'my-agree-input',
                },
            },
            fieldOptionLabel: {
                attributes: {
                    class: 'my-agree-label',
                },
            },
        },
    },
}) }}
```

Use theme config for class and attribute changes. Use a template override only when the checkbox and description markup needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Front-end reference

The front-end docs live on the separate browser UI reference site and cover rendered markup, data attributes, styling classes and JavaScript behavior for custom front-end implementations.

- [Agree](/browser/ui-reference/fields/agree)

## Related fields

- Use [Checkboxes](/fields/checkboxes) when the user can choose several acknowledgements.
- Use [Radio](/fields/radio) when the user must choose between multiple explicit options.

