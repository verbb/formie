# HTML

Use HTML when the form needs custom markup or Twig between fields.

HTML is a cosmetic field for developers and agencies who need full control over markup, Twig includes, and layout fragments. If the content is just formatted copy for non-technical editors, use [Rich Text](/fields/content) instead.

## Key settings

- **HTML content** - Define the markup rendered in the form, edited with a syntax-highlighted code editor in the form builder.
- **Allow Twig** - When enabled, Twig in the HTML content is parsed in Formie’s sandbox when the form is rendered. This is separate from Craft notification templates and does not grant full CP Twig access.
- **Purify content** - When enabled, output is passed through HTML Purifier before rendering.
- **Visibility conditions** - Show or hide the content based on other form values when needed.

## Submitted value

HTML is cosmetic and does not save a normal submitted value. Content is rendered at display time from the field settings.

## Theme config

The HTML field can be targeted with the `html` theme config key.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        html: {
            field: {
                attributes: {
                    class: 'my-html-block',
                },
            },
        },
    },
}) }}
```

Use theme config for wrapper attributes. Use a template override only when the cosmetic field output needs to change globally.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Related fields

- Use [Rich Text](/fields/content) for WYSIWYG content editable by non-technical users.
- Use [Heading](/fields/heading) for simple section titles.
- Use [Section](/fields/section) for visual dividers.
- Use [Group](/fields/group) when fields should be structurally grouped.
