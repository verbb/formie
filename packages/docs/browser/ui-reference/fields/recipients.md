# Recipients

Recipients is the routing field that can render as standard choice inputs.

Use this page as the reference for the display variants the field can render as: dropdown, multi-dropdown, checkboxes, and radio buttons.

## Preview

<FormiePreview src="../examples/recipients.preview.ts" />

## Attributes

Recipients keeps its own field identity, but the rendered control inherits the attributes of the chosen display type:

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-field-type="recipients"` | Field identity marker on the outer wrapper | Required |
| `data-formie-input` and `data-formie-input-id` | Shared input identity hooks from the rendered choice control | Required |
| `<select>` / `data-formie-checkbox-input` / `data-formie-radio-input` | Control-level hooks inherited from the selected display type | Required |
| Hidden empty input for checkbox display | Preserves empty-state submission behavior | Recommended for checkbox display |

## Display types

Recipients can render as:

- a single-select dropdown
- a multi-select dropdown
- a checkbox group
- a radio group

The backend decides which display type is rendered, but the browser layer uses the same attributes as the corresponding choice field.

## Related pages

- [Checkboxes](/browser/ui-reference/fields/checkboxes)
- [Radio](/browser/ui-reference/fields/radio)
- [Entries](/browser/ui-reference/fields/entries)
- [Categories](/browser/ui-reference/fields/categories)
