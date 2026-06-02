# Tags

Use Tags when the user should choose or create Craft tag elements.

Use Tags when the answer should become or relate to Craft tags. If the options should be fixed and managed only in the form builder, use Dropdown, Radio, or Checkboxes instead.

## Key settings

- **Tag group** - Choose which tag group the field should use.
- **New tag creation** - Decide whether the field can create new tags or only select existing tags.
- **Display type** - Choose how tag choices appear on the front end.
- **Placeholder** - Set the initial empty option text where the selected display type supports it.
- **Selection limit** - Control how many tags can be selected where supported.

## Submitted value

Tags stores references to Craft tag elements. Templates, exports and integrations can use the related tag data instead of relying on copied text.

When querying or saving submissions through GraphQL, relation fields can expose element-aware content. Query the form’s `formFields` and include `inputTypeName` when building mutations.

## Theme config

The Tags field can be targeted with the `tags` theme config key.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        tags: {
            fieldInput: {
                attributes: {
                    class: 'my-tags-input',
                },
            },
        },
    },
}) }}
```

Use theme config for class and attribute changes. Use a template override only when the tag field markup needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Front-end reference

The front-end docs live on the separate browser UI reference site and cover rendered markup, data attributes, styling classes and JavaScript behavior for custom front-end implementations.

- [Tags](/browser/ui-reference/fields/tags)

## Related fields

- Use [Categories](/fields/categories) when the taxonomy should be hierarchical.
- Use [Dropdown](/fields/dropdown), [Radio](/fields/radio) or [Checkboxes](/fields/checkboxes) for fixed option lists.

