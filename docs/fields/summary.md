# Summary

Use Summary when the form should show a review of entered values before final submission.

Summary is a cosmetic field, not a normal input field. It is most useful near the end of a multi-page form where someone should confirm their answers before finishing.

## Key settings

- **Included fields** - Choose which submitted values should appear in the review.
- **Display options** - Control how field labels and values are shown.
- **Page placement** - Place the summary near the end of a multi-page form when the user can still go back and edit values.

## Submitted value

Summary is cosmetic and does not save a normal submitted value. It reflects other field values using Formie’s normal value formatting.

## Theme config

The Summary field can be targeted with the `summary` theme config key.

See [Summary Field theme config](/theming/theme-config#summary-field) for the full list of field-specific theme tags.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        summary: {
            fieldSummaryContainer: {
                attributes: {
                    class: 'my-summary-container',
                },
            },
            fieldSummaryItem: {
                attributes: {
                    class: 'my-summary-item',
                },
            },
        },
    },
}) }}
```

Use theme config for class and attribute changes. Use a template override only when the summary layout needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Front-end reference

Summary output should stay in sync with the current submission state. If you customize rendering, keep Formie’s front-end JavaScript available so dynamic values can refresh correctly.

The front-end docs live on the separate browser UI reference site and cover rendered markup, data attributes, styling classes and JavaScript behavior for custom front-end implementations.

- [Summary](/browser/ui-reference/fields/summary)

## Related fields

- Use [Calculations](/fields/calculations) when the form needs a derived value, not a review block.

