# Number

Use Number when the answer should be treated as a numeric value.

Use Number when the value needs numeric validation, comparison, calculations, or export as a number. If the value may contain leading zeroes, spaces, letters, or formatting characters, Single-Line Text is usually safer.

## Key settings

- **Minimum and maximum values** - Restrict the accepted numeric range.
- **Decimal places** - Control how the value is formatted.
- **Placeholder** - Show example text before the user enters a value.
- **Default value** - Pre-fill the field for new submissions.
- **Unique value** - Prevent the same number from being submitted more than once for the form.

## Submitted value

Number stores a numeric value. Use Single-Line Text instead when the answer may contain leading zeroes, separators, spaces or other characters that should be preserved exactly.

When querying or saving submissions through GraphQL, the field handle is used as the field name. Query the form’s `formFields` and include `inputTypeName` if you need to confirm the generated input type for a specific form.

## Theme config

The Number field can be targeted with the `number` theme config key.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        number: {
            fieldInput: {
                attributes: {
                    class: 'my-number-input',
                },
            },
        },
    },
}) }}
```

Use theme config for class and attribute changes. Use a template override only when the numeric input markup needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Related fields

- Use [Calculations](/fields/calculations) when the value should be derived from other fields.
- Use [Single-Line Text](/fields/single-line-text) for codes, IDs or values with formatting characters.

