# Categories

Use Categories when the user should choose from Craft category elements.

Use Categories when the answer should stay connected to Craft categories. If you only need a static list managed inside the form, Dropdown, Radio, or Checkboxes is usually easier.

## Key settings

- **Category source** - Choose which category group or source the user can select from.
- **Root category** - Start the selector from a specific branch.
- **Show structure** - Preserve the category hierarchy in the displayed choices.
- **Branch limit** - Restrict how deep the selectable category tree can go.
- **Selection limit** - Control how many categories can be selected.
- **Display type** - Choose how the category choices appear on the front end.
- **Placeholder** - Set the initial empty option text where the selected display type supports it.

## Submitted value

Categories stores references to Craft category elements. Templates, exports and integrations can use the related category data instead of relying on a copied label.

When querying or saving submissions through GraphQL, relation fields can expose element-aware content. Query the form’s `formFields` and include `inputTypeName` when building mutations.

## Theme config

The Categories field can be targeted with the `categories` theme config key.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        categories: {
            fieldInput: {
                attributes: {
                    class: 'my-categories-input',
                },
            },
        },
    },
}) }}
```

Use theme config for class and attribute changes. Use a template override only when the category field markup needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Front-end reference

The front-end docs live on the separate browser UI reference site and cover rendered markup, data attributes, styling classes and JavaScript behavior for custom front-end implementations.

- [Categories](/browser/ui-reference/fields/categories)

## Related fields

- Use [Entries](/fields/entries), [Tags](/fields/tags) or [Users](/fields/users) for other Craft element relations.
- Use [Dropdown](/fields/dropdown), [Radio](/fields/radio) or [Checkboxes](/fields/checkboxes) for static option lists managed inside the form.

