# Multi-Line Text

Use Multi-Line Text when the answer can run across several lines.

Use it for comments, messages, longer explanations, and notes. If the answer should stay short and fit on one line, use Single-Line Text instead.

## Key settings

- **Placeholder** - Show example text before the user enters a value.
- **Default value** - Pre-fill the field for new submissions.
- **Rows** - Control the visible height of the textarea.
- **Character or word limits** - Enforce minimum and maximum response length.
- **Rich text** - Allow formatted content instead of plain text.

## Submitted value

Multi-Line Text stores text content. With rich text disabled, treat the value as plain text. With rich text enabled, expect formatted content and account for that in emails, exports, integrations and template output.

When querying or saving submissions through GraphQL, the field handle is used as the field name. Query the form’s `formFields` and include `inputTypeName` if you need to confirm the generated input type for a specific form.

## Theme config

The Multi-Line Text field can be targeted with the `multiLineText` theme config key.

See [Multi-Line Text Field theme config](/theming/theme-config#multi-line-text-field) for the full list of field-specific theme tags.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        multiLineText: {
            fieldInput: {
                attributes: {
                    class: 'my-textarea',
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

Use theme config for class and attribute changes. Use a template override only when the textarea or rich-text markup needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Front-end reference

The front-end docs live on the separate browser UI reference site and cover rendered markup, data attributes, styling classes and JavaScript behavior for custom front-end implementations.

- [Multi Line Text](/browser/ui-reference/fields/multi-line-text)

## Related fields

- Use [Single-Line Text](/fields/single-line-text) for shorter answers.
- Use [HTML](/fields/html) for cosmetic formatted content inside the form.

