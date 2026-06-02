# Date/Time

Use Date/Time when the form needs a date, time, or both.

Use Date/Time when the answer should behave like a real date or time. If the answer is vague, such as “Spring” or “next quarter,” Single-Line Text may be a better fit.

## Key settings

- **Date and time mode** - Collect a date, a time, or both.
- **Display type** - Render as a calendar picker, dropdowns or text-style inputs.
- **Date and time formats** - Control how the value is displayed to the user.
- **Minimum and maximum dates** - Restrict the accepted range with fixed or relative limits.
- **Default value** - Pre-fill the field for new submissions.
- **Date picker options** - Pass additional Flatpickr options when the calendar picker is enabled.

## Submitted value

Date/Time stores date/time data rather than vague text. Templates, exports and integrations should treat it as a date/time value and format it for the destination context.

When querying or saving submissions through GraphQL, Date/Time fields can expose a field-specific content type. Query the form’s `formFields` and include `inputTypeName` when building mutations.

## Theme config

The Date/Time field can be targeted with the `dateTime` theme config key.

See [Date/Time Field theme config](/theming/theme-config#date-time-field) for the full list of field-specific theme tags.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        dateTime: {
            subFieldRows: {
                attributes: {
                    class: 'my-date-rows',
                },
            },
            fieldInput: {
                attributes: {
                    class: 'my-date-input',
                },
            },
        },
    },
}) }}
```

Use theme config for class and attribute changes. Use a template override only when the calendar, dropdown or input markup needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Front-end reference

When the date picker is enabled, Formie relies on Flatpickr and the field’s browser assets. Custom-rendered forms must still include the required assets and preserve the field inputs Formie expects.

Use the Date Picker Options setting for Flatpickr-specific configuration, such as disabling dates or changing picker behavior. See the [Flatpickr documentation](https://flatpickr.js.org/) for supported options.

The front-end docs live on the separate browser UI reference site and cover rendered markup, data attributes, styling classes and JavaScript behavior for custom front-end implementations.

- [Date](/browser/ui-reference/fields/date)

## Related fields

- Use [Single-Line Text](/fields/single-line-text) for approximate dates or informal time periods.
- Use [Calculations](/fields/calculations) when a displayed value should be computed from date-related inputs.

