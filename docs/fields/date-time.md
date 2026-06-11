# Date/Time

Use Date/Time when the form needs a date, time, or both.

Use Date/Time when the answer should behave like a real date or time. If the answer is vague, such as “Spring” or “next quarter,” Single-Line Text may be a better fit.

## Key settings

- **Date and time mode** - Collect a date, a time, or both.
- **Display type** - Render as a calendar picker, dropdowns or text-style inputs.
- **Value type** - When **Display type** is **Calendar (Advanced)**, choose **Single Date/Time** or **Date Range**.
- **Date and time formats** - Control how the value is displayed to the user.
- **Minimum and maximum dates** - Restrict the accepted range with fixed or relative limits. These apply to calendar, dropdown, and text-input display types.
- **Default value** - Pre-fill the field for new submissions.
- **Date picker options** - Pass additional Flatpickr options when the calendar picker is enabled.

## Date range

When **Display type** is **Calendar (Advanced)**, set **Value type** to **Date Range** to collect a start and end date/time in a single field.

Date range is only available for **Calendar (Advanced)**. Other display types continue to collect a single date/time value.

The front end uses Flatpickr range mode. Submitted values are stored as start/end part maps and formatted for output using the field's **Date Format** and **Time Format** settings.

## Submitted value

Date/Time stores date/time data rather than vague text. Templates, exports and integrations should treat it as a date/time value and format it for the destination context.

Single values are stored as part maps (`year`, `month`, `day`, and optional time parts). Date range values are stored with `start` and `end` part maps:

```json
{
  "start": { "year": "2026", "month": "6", "day": "16", "hour": "12", "minute": "0" },
  "end": { "year": "2026", "month": "6", "day": "27", "hour": "12", "minute": "0" }
}
```

Internal part values are stored without leading zeros. Formatted output always uses the field's configured formats.

### Output and templates

For a single date/time field:

- `submission.getFieldValue('myDate')` returns a value object.
- Casting that value to string, or calling `submission.getFieldValueAsString('myDate')`, returns the same formatted value.
- Nested paths such as `myDate.date` and `myDate.time` return formatted date and time strings.

For a date range field:

- The primary value stringifies as `2026-06-16 12:00 – 2026-06-27 12:00` (using the field formats).
- Nested paths include `start`, `end`, `startDate`, `startTime`, `endDate`, and `endTime`.

Individual part selectors such as `{field:myDate:month}` still return the raw stored part value when using dropdown or text-input display types.

For **Text Inputs** and **Dropdowns**, each date/time part is a number or option field with sensible defaults (for example, month 1–12 and day 1–31). You can adjust limits per part from the nested sub-field editor. Formie also validates that the combined date parts form a real calendar date, and enforces any configured minimum or maximum dates on submit.

When querying or saving submissions through GraphQL, Date/Time fields can expose a field-specific content type. Query the form’s `formFields` and include `inputTypeName` when building mutations. Date range fields expose a form-specific type with `start` and `end` datetime values.

## Notification variables

Date/Time fields expose variable selectors in the notification editor based on the field configuration:

| Selector | Available when |
| --- | --- |
| Formatted Date | Always |
| Date | Single calendar or single advanced picker |
| Time | Single calendar or single advanced picker |
| Start Date/Time | Date range |
| End Date/Time | Date range |
| Start Date, Start Time, End Date, End Time | Date range |
| Year, Month, Day, Hour, Minute, Second, AM/PM | Dropdowns or text inputs |

All composite datetime selectors use the field's **Date Format** and **Time Format** settings.

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

Date range fields submit hidden start/end transport inputs in addition to the visible picker input. Preserve `data-formie-date-range-start-input` and `data-formie-date-range-end-input` when overriding templates.

Use the Date Picker Options setting for Flatpickr-specific configuration, such as disabling dates or changing picker behavior. See the [Flatpickr documentation](https://flatpickr.js.org/) for supported options.

The front-end docs live on the separate browser UI reference site and cover rendered markup, data attributes, styling classes and JavaScript behavior for custom front-end implementations.

- [Date](/browser/ui-reference/fields/date)

## Related fields

- Use [Single-Line Text](/fields/single-line-text) for approximate dates or informal time periods.
- Use [Calculations](/fields/calculations) when a displayed value should be computed from date-related inputs.
