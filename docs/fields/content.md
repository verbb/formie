# Rich Text

Use Rich Text when non-technical editors need formatted content between fields.

Rich Text is a cosmetic field for explanatory copy, bullet lists, links, and basic headings. If you need raw HTML or Twig, use [HTML](/fields/html) instead.

## Key settings

- **Content** - Define the formatted content rendered in the form, edited with the form builder rich text editor.
- **Visibility conditions** - Show or hide the content based on other form values when needed.
- **Field position** - Place the content near the fields it explains.

Toolbar buttons and editor height can be configured project-wide via [Rich Text Configuration](/get-started/configuration#rich-text-configuration) under the `fields.content` key.

## Submitted value

Rich Text is cosmetic and does not save a normal submitted value. Content is stored as part of the field settings and rendered on the front end.

## Theme config

The Rich Text field can be targeted with the `content` theme config key.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        content: {
            field: {
                attributes: {
                    class: 'my-content-block',
                },
            },
        },
    },
}) }}
```

Use theme config for wrapper attributes. Use a template override only when the cosmetic field output needs to change globally.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Related fields

- Use [HTML](/fields/html) for developer-authored markup and Twig.
- Use [Heading](/fields/heading) for simple section titles.
- Use [Section](/fields/section) for visual dividers.
- Use [Group](/fields/group) when fields should be structurally grouped.
