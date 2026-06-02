# Heading

Use Heading when the form needs a clear text heading between fields.

Heading is a cosmetic field. Use it for section titles, not longer explanatory content. For longer copy or markup, HTML may be a better fit.

## Key settings

- **Heading text** - The visible title shown in the form.
- **Heading size** - Choose the heading level or visual size that matches the section’s importance.
- **Visibility conditions** - Show or hide the heading based on other form values when needed.

## Submitted value

Heading is cosmetic and does not save a normal submitted value.

## Theme config

The Heading field can be targeted with the `heading` theme config key.

See [Heading Field theme config](/theming/theme-config#heading-field) for the full list of field-specific theme tags.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        heading: {
            fieldHeading: {
                tag: 'h2',
                attributes: {
                    class: 'my-form-heading',
                },
            },
        },
    },
}) }}
```

Use theme config for the heading tag, classes and attributes. Use a template override only when the heading output needs to change globally.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Related fields

- Use [HTML](/fields/html) for longer explanatory copy or custom markup.
- Use [Section](/fields/section) for a visual divider.
- Use [Group](/fields/group) when fields should be structurally grouped.

