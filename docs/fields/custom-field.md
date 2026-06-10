# Custom Field

The Custom Field field lets a form use supported Craft fields through Formie without adding a separate Formie field type for every provider.

Custom Field is adapter-based. Each available option in **Custom Field Type** has explicit Formie support for rendering, validation, submissions, email summaries, exports, integrations and GraphQL.

## Supported adapters

Formie includes these adapters:

- **URL** for scalar URL values.
- **Address (Google Maps)** for supported Google Maps Craft field classes, when the plugin is installed.
- **Maps** for supported Maps/SimpleMap Craft field classes, when the plugin is installed.

Only available adapters are shown in the field settings. Third-party developers can register additional adapters for their own Craft fields.

## Field settings

1. Add **Custom Field** to a form.
2. Choose a **Custom Field Type** in the picker modal.
3. Configure the field in the regular field settings modal.

The Custom Field Type is chosen once when the field is created. It cannot be changed later because each adapter can store a different value shape. To use a different adapter, add a new Custom Field.

The settings change depending on the selected adapter. For example, URL fields expose URL-oriented placeholder and default-value settings, while map adapters expose address and coordinate defaults.

## Notes

Custom Field is not automatic support for every Craft field class. A Craft field needs a Formie adapter so its value can be submitted, stored, displayed and exported consistently.

For adapter development, see [Custom Field adapters](/developers/custom-field#custom-field-adapters).
