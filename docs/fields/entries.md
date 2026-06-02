# Entries

Use Entries when the user should choose from Craft entry elements.

Use Entries when the selected content should stay related to Craft entries. If you only need a static list controlled inside the form builder, Dropdown, Radio, or Checkboxes will usually be simpler.

## Key settings

- **Entry sources** - Choose which sections or entry sources the user can select from.
- **Selection limit** - Control how many entries can be selected.
- **Display type** - Choose how the entry choices appear on the front end.
- **Placeholder** - Set the initial empty option text where the selected display type supports it.
- **Label format** - Control how entries are labelled where supported.

## Submitted value

Entries stores references to Craft entry elements. Templates, exports and integrations can use the related entry data instead of relying on a copied title.

When querying or saving submissions through GraphQL, relation fields can expose element-aware content. Query the form’s `formFields` and include `inputTypeName` when building mutations.

## Theme config

The Entries field can be targeted with the `entries` theme config key.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        entries: {
            fieldInput: {
                attributes: {
                    class: 'my-entries-input',
                },
            },
        },
    },
}) }}
```

Use theme config for class and attribute changes. Use a template override only when the entry field markup needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Front-end reference

The front-end docs live on the separate browser UI reference site and cover rendered markup, data attributes, styling classes and JavaScript behavior for custom front-end implementations.

- [Entries](/browser/ui-reference/fields/entries)

## Related fields

- Use [Categories](/fields/categories), [Tags](/fields/tags) or [Users](/fields/users) for other Craft element relations.
- Use [Dropdown](/fields/dropdown), [Radio](/fields/radio) or [Checkboxes](/fields/checkboxes) for static option lists managed inside the form.

