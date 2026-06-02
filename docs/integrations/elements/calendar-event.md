# Calendar Event
For a form, you can configure [Solspace Calendar](https://plugins.craftcms.com/calendar?craft5) events to be created for submissions.

> [!NOTE]
> This integration requires the [Solspace Calendar](https://plugins.craftcms.com/calendar?craft5) plugin to be installed, because Formie is creating Calendar events directly.

You'll need to configure:

- Calendar
- Default Event Author
- Event Attribute Mapping
- Event Field Mapping
- Overwrite Content
- Update Events
- Update Element Mapping

### Mapping
For both event attributes and any custom fields on the selected calendar, you can assign a field's content to be mapped to that attribute or field.

The attribute mapping supports values like:

- Title
- Site ID
- Slug
- Author
- Start Date
- End Date
- All Day
- Enabled
- Repeat Rule
- Repeat Interval
- Repeat Frequency
- Repeat Count
- Repeat Until
- Repeat By Month
- Repeat Year Day
- Repeat By Month Day
- Repeat By Day
- Select Dates

If you turn on **Update Events**, Formie can look for an existing Calendar event and update it instead of always creating a new one.
