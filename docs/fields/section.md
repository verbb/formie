# Section

Use Section when the form needs a visual divider between groups of fields.

Section is a cosmetic field. Use Heading when the break needs a title, and use Group when fields should be structurally grouped rather than only visually separated.

## Key Settings

- **Border visibility** - Show or hide the divider line.
- **Border width** - Set the divider thickness.
- **Border color** - Set the divider colour.
- **Visibility conditions** - Show or hide the divider based on other form values when needed.

## Submitted Value

Section is cosmetic and does not save a normal submitted value.

## Theme Config

The Section field can be targeted with the `section` theme config key.

See [Section Field theme config](/reference/theme-tag-reference#section-field) for the full list of field-specific theme tags.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        section: {
            fieldSection: {
                attributes: {
                    class: 'my-form-section',
                },
            },
        },
    },
}) }}
```

Use theme config for divider classes, attributes and tag changes. Use a template override only when the section output needs to change globally.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Related Fields

- Use [Heading](/fields/heading) when the break needs a title.
- Use [HTML](/fields/html) for explanatory copy or custom markup.
- Use [Group](/fields/group) when fields should be structurally grouped.

