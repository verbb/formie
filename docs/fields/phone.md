# Phone

Use Phone when the answer should be a phone number.

Use Phone when the value will be treated as a phone number later, such as in CRM mapping or contact workflows. Use Single-Line Text if the value should not be validated as a phone number.

## Key Settings

- **Country selector** - Let the user choose the country context for the number.
- **Default country** - Preselect the most likely country.
- **Allowed countries** - Restrict submissions to numbers from selected countries.
- **Placeholder** - Show example text before the user enters a value.
- **Required** - Force a phone number before the form can be submitted.

## Submitted Value

Phone stores phone-number data rather than arbitrary text. Depending on the field configuration, templates, summaries and integrations can use the value with country context.

When querying or saving submissions through GraphQL, the field handle is used as the field name. Query the form’s `formFields` and include `inputTypeName` if you need to confirm the generated input type for a specific form.

## Theme Config

The Phone field can be targeted with the `phoneNumber` theme config key.

See [Phone Field theme config](/reference/theme-tag-reference#phone-field) for the full list of field-specific theme tags.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        phoneNumber: {
            fieldInput: {
                attributes: {
                    class: 'my-phone-input',
                },
            },
            fieldCountryInput: {
                attributes: {
                    class: 'my-phone-country-input',
                },
            },
        },
    },
}) }}
```

Use theme config for class and attribute changes. Use a template override only when the phone input or country selector markup needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Front-End Reference

The front-end docs live on the separate browser UI reference site and cover rendered markup, data attributes, styling classes and JavaScript behaviour for custom front-end implementations.

- [Phone](https://docs.verbb.io/formie/browser/ui-reference/fields/phone)

## Related Fields

- Use [Single-Line Text](/fields/single-line-text) if the value should preserve arbitrary formatting and not be validated as a phone number.
- Use [Name](/fields/name) and [Email Address](/fields/email-address) for adjacent contact details.

