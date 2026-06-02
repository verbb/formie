# Hidden

Use Hidden when the form needs to carry a value without showing it to the person filling out the form.

Hidden fields are useful for context, attribution, and routing data. Do not rely on them for security-sensitive decisions because hidden values can still be changed before submission.

## Key settings

- **Default value** - Set the value submitted with the form.
- **Value source** - Populate from a custom value, URL, query string parameter, cookie or user-related context where supported.
- **Prefill query parameter** - Read a value from a specific query string parameter.
- **Handle** - Choose a stable field handle for integrations, templates and exports.

## Submitted value

Hidden stores a submitted value like other fields, even though the user does not see a visible control. Treat it as user-modifiable browser data unless you set or validate it server-side.

When querying or saving submissions through GraphQL, the field handle is used as the field name. Query the form’s `formFields` and include `inputTypeName` if you need to confirm the generated input type for a specific form.

## Theme config

The Hidden field can be targeted with the `hiddenField` theme config key.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        hiddenField: {
            field: {
                attributes: {
                    data: {
                        tracking: 'campaign',
                    },
                },
            },
        },
    },
}) }}
```

Hidden fields usually need little presentation customization. Use a template override only when the hidden input output itself needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Front-end reference

Hidden fields are still rendered into the page HTML. Do not place secrets, trusted prices, permission flags or other security-sensitive values in a hidden field without server-side validation.

The front-end docs live on the separate browser UI reference site and cover rendered markup, data attributes, styling classes and JavaScript behavior for custom front-end implementations.

- [Hidden](/browser/ui-reference/fields/hidden)

## Related fields

- Use [Single-Line Text](/fields/single-line-text) when the user should see and edit the value.

