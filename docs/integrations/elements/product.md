# Product
For a form, you can configure [Craft Commerce](https://plugins.craftcms.com/commerce?craft5) products to be created for submissions.

> [!NOTE]
> This integration requires [Craft Commerce](https://plugins.craftcms.com/commerce?craft5) and currently supports the default single-variant product flow.

You'll need to configure:

- Product Type
- Default Author
- Attribute Mapping
- Field Mapping
- Overwrite Content
- Update Products
- Update Element Mapping

### Mapping
For both product attributes and any custom fields on the selected product type, you can assign a field's content to be mapped to that attribute or field.

The attribute mapping supports product values like:

- Title
- Site ID
- Slug
- Author
- Post Date
- Expiry Date
- Enabled
- Date Created
- Date Updated

It also supports the default variant fields:

- SKU
- Price
- Stock
- Unlimited Stock
- Minimum allowed quantity
- Maximum allowed quantity
- Length
- Width
- Height
- Weight

If you turn on **Update Products**, Formie can look for an existing product and update it instead of always creating a new one.
