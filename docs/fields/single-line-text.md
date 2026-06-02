# Single-Line Text

Use Single-Line Text when the answer should fit on one line but does not need a more specialized field such as Email Address, Phone, or Number.

Use it for short free-form answers, names of things, codes, references, and simple labels.

## Key settings

- **Placeholder** - Show example text before the user enters a value.
- **Default value** - Pre-fill the field for new submissions.
- **Character or word limits** - Enforce minimum and maximum response length.
- **Unique value** - Prevent the same value from being submitted more than once for the form.
- **Match field** - Require this value to match another field, usually for confirmation-style flows.

## Submitted value

Single-Line Text stores a plain string. It is usually the safest field for short values that might include letters, numbers, punctuation, leading zeroes or formatting.

When querying or saving submissions through GraphQL, the field handle is used as the field name. Query the form’s `formFields` and include `inputTypeName` if you need to confirm the generated input type for a specific form.

## Theme config

The Single-Line Text field can be targeted with the `singleLineText` theme config key.

See [Single-Line Text Field theme config](/theming/theme-config#single-line-text-field) for the full list of field-specific theme tags.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        singleLineText: {
            fieldInput: {
                attributes: {
                    class: 'my-text-input',
                },
            },
            fieldLimit: {
                attributes: {
                    class: 'my-text-limit',
                },
            },
        },
    },
}) }}
```

Use theme config for class and attribute changes. Use a template override only when the input markup needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Front-end reference

The front-end docs live on the separate browser UI reference site and cover rendered markup, data attributes, styling classes and JavaScript behavior for custom front-end implementations.

- [Single Line Text](/browser/ui-reference/fields/single-line-text)

## Related fields

- Use [Email Address](/fields/email-address) for email-specific validation.
- Use [Phone](/fields/phone) for phone-number formatting and country handling.
- Use [Number](/fields/number) when the value should be validated and processed as a number.

