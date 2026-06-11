# Variants

Use Variants when the user should choose from Craft Commerce variant elements.

Use Variants when the selected value should identify the exact purchasable or product option. If the form only needs a simple static product choice, Dropdown, Radio, or Checkboxes will usually be easier to maintain.

## Key settings

- **Variant sources** - Choose which Commerce variant sources are available.
- **Selection limit** - Control how many variants can be selected.
- **Display type** - Choose how variant choices appear on the front end.
- **Use searchable dropdown** - When **Display type** is **Dropdown**, allow users to filter options by typing. See [Dropdown → Searchable dropdown](/fields/dropdown#searchable-dropdown).
- **Placeholder** - Set the initial empty option text where the selected display type supports it.
- **Label format** - Control how variants are labelled where supported.

## Submitted value

Variants stores references to Craft Commerce variant elements. Use this when the submitted value needs to identify the exact purchasable option.

When querying or saving submissions through GraphQL, relation fields can expose element-aware content. Query the form’s `formFields` and include `inputTypeName` when building mutations.

## Requirements

[Craft Commerce](https://plugins.craftcms.com/commerce?craft5) is required for this field to be available in the form builder.

## Theme config

The Variants field can be targeted with the `variants` theme config key.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        variants: {
            fieldInput: {
                attributes: {
                    class: 'my-variants-input',
                },
            },
        },
    },
}) }}
```

Use theme config for class and attribute changes. Use a template override only when the variant field markup needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Related fields

- Use [Products](/fields/products) when selecting the product is enough.
- Use [Dropdown](/fields/dropdown), [Radio](/fields/radio) or [Checkboxes](/fields/checkboxes) for static product-like options.

