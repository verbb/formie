# Custom Field

::: tip
For plugin developers building a new adapter, see [Bringing your Craft field into Formie (Custom Field adapters)](/guides/fields/bringing-your-craft-field-into-formie-custom-field-adapters).
:::

The Custom Field field lets a form use supported Craft fields through Formie without adding a separate Formie field type for every provider.

Custom Field is adapter-based. Each available option in **Custom Field Type** has explicit Formie support for rendering, validation, submissions, email summaries, exports, integrations and GraphQL.

## Supported adapters

Formie includes these adapters:

- **Link** for URL, email, phone and SMS link values from Craft’s Link field.
- **Address (Google Maps)** for supported Google Maps Craft field classes, when the plugin is installed.
- **Maps** for supported Maps/SimpleMap Craft field classes, when the plugin is installed.

Only available adapters are shown in the field settings. Third-party developers can register additional adapters for their own Craft fields.

## Field settings

1. Add **Custom Field** to a form.
2. Choose a **Custom Field Type** in the picker modal.
3. Configure the field in the regular field settings modal.

The Custom Field Type is chosen once when the field is created. It cannot be changed later because each adapter can store a different value shape. To use a different adapter, add a new Custom Field.

The settings change depending on the selected adapter. For example, Link fields expose allowed link types, URL options, label and advanced-attribute settings, while map adapters expose address and coordinate defaults, map visibility, latitude/longitude display, current-location behaviour and zoom options where supported.

The Link adapter supports the text-based Craft Link types that can be rendered on public forms without Craft control-panel element selectors: URL, Email, Phone and SMS. Entry, Asset and Category link types are not included in the front-end adapter.

When a Link field allows one link type, Formie renders a single value input and stores the type in a hidden input. When multiple link types are enabled, Formie renders a type selector and uses a small front-end enhancement to update the value input’s native type and keyboard hints as the user switches between URL, Email, Phone and SMS.

## Notes

Custom Field is not automatic support for every Craft field class. A Craft field needs a Formie adapter so its value can be submitted, stored, displayed and exported consistently.

For adapter development, see [Custom Field adapters](/developers/custom-field#custom-field-adapters).
