# Events Event
For a form, you can configure [Verbb Events](https://plugins.craftcms.com/events?craft5) events to be created for submissions.

> [!NOTE]
> This integration requires the [Verbb Events](https://plugins.craftcms.com/events?craft5) plugin to be installed, because Formie is creating Events elements directly.

You'll need to configure:

- Event Type
- Default Event Author
- Event Attribute Mapping
- Event Field Mapping
- Overwrite Content
- Update Events
- Update Element Mapping

### Mapping
For both event attributes and any custom fields on the selected event type, you can assign a field's content to be mapped to that attribute or field.

The attribute mapping supports values like:

- Title
- Site ID
- Slug
- Author
- Start Date
- End Date
- All Day
- Enabled

If you turn on **Update Events**, Formie can look for an existing Events event and update it instead of always creating a new one.
