# Products

Use Products when the user should choose from Craft Commerce product elements.

Use Products when the selected value should be a product relation. If the form only needs a simple pricing choice or static product-like label, Dropdown, Radio, or Checkboxes may be easier to maintain.

## Key settings

- **Product sources** - Choose which Commerce product sources are available.
- **Selection limit** - Control how many products can be selected.
- **Display type** - Choose how product choices appear on the front end.
- **Use searchable dropdown** - When **Display type** is **Dropdown**, allow users to filter options by typing. See [Dropdown → Searchable dropdown](/fields/dropdown#searchable-dropdown).
- **Placeholder** - Set the initial empty option text where the selected display type supports it.
- **Label format** - Control how products are labelled where supported.

## Submitted value

Products stores references to Craft Commerce product elements. Templates, exports and integrations can use the related product data instead of only a copied label.

When querying or saving submissions through GraphQL, relation fields can expose element-aware content. Query the form’s `formFields` and include `inputTypeName` when building mutations.

## Requirements

[Craft Commerce](https://plugins.craftcms.com/commerce?craft5) is required for this field to be available in the form builder.

## Theme config

The Products field can be targeted with the `products` theme config key.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        products: {
            fieldInput: {
                attributes: {
                    class: 'my-products-input',
                },
            },
        },
    },
}) }}
```

Use theme config for class and attribute changes. Use a template override only when the product field markup needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Related fields

- Use [Variants](/fields/variants) when the exact purchasable variant matters.
- Use [Dropdown](/fields/dropdown), [Radio](/fields/radio) or [Checkboxes](/fields/checkboxes) for static product-like options.

