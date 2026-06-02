# Name

Use Name when the form asks for a person’s name and you want Formie to understand it as name data.

Use Name for person names that may need to be displayed, exported, or mapped as name parts. For non-person labels, titles, or codes, Single-Line Text is usually a better fit.

## Key settings

- **Single or multiple inputs** - Choose whether the name is collected as one value or separate sub-fields.
- **Enabled sub-fields** - Choose which parts are shown, such as prefix, first name, middle name and last name.
- **Required sub-fields** - Require the specific parts your workflow needs.
- **Placeholder and default values** - Guide or pre-fill individual name inputs where supported.

## Submitted value

Name stores name data in the configured shape. With multiple inputs enabled, templates, exports and integrations can work with separate name parts.

For GraphQL mutations, Name fields use a generated input object for the field handle. Query the form’s `formFields` and include `inputTypeName`, or see [Create Submissions](/graphql/create-submissions#name-and-address-fields).

## Theme config

The Name field can be targeted with the `name` theme config key.

See [Name Field theme config](/theming/theme-config#name-field) for the full list of field-specific theme tags.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        name: {
            subFieldRows: {
                attributes: {
                    class: 'my-name-rows',
                },
            },
            fieldInput: {
                attributes: {
                    class: 'my-name-input',
                },
            },
        },
    },
}) }}
```

Some name sub-fields can also be targeted by theme config, such as `namePrefix`, `nameFirst`, `nameMiddle` and `nameLast`.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Related fields

- Use [Single-Line Text](/fields/single-line-text) for non-person names, titles, labels or codes.
- Use [Email Address](/fields/email-address) and [Phone](/fields/phone) for adjacent contact details.

