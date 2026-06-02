# HTML

Use HTML when the form needs custom markup between fields.

HTML is a cosmetic field for explanatory copy, embedded markup, notices, links, or small layout fragments. If the content is just a title or divider, Heading or Section is usually simpler.

## Key settings

- **HTML content** - Define the markup rendered in the form.
- **Visibility conditions** - Show or hide the content based on other form values when needed.
- **Field position** - Place the content near the fields it explains.

## Submitted value

HTML is cosmetic and does not save a normal submitted value. It can affect the user’s understanding of the form, but it is not submission content.

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

- Use [Heading](/fields/heading) for simple section titles.
- Use [Section](/fields/section) for visual dividers.
- Use [Group](/fields/group) when fields should be structurally grouped.

