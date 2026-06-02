# Entries

Entries is an element field that can render as standard choice inputs.

Use this page as the reference for the display variants you need to preserve when mocking or restyling entry selection.

## Preview

<FormiePreview src="../examples/entries.preview.ts" />

## Attributes

Entries keeps its own field identity, but the visible control inherits the attributes of the chosen display type:

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-field-type="entries"` | Field identity marker on the outer wrapper | Required |
| `data-formie-input` and `data-formie-input-id` | Shared input identity hooks from the rendered choice control | Required |
| `<select>` / `data-formie-checkbox-input` / `data-formie-radio-input` | Control-level hooks inherited from the selected display type | Required |
| Hidden empty input for checkbox display | Preserves empty-state submission behavior | Recommended for checkbox display |

## Display types

Entries can render as:

- a single-select dropdown
- a multi-select dropdown
- a checkbox group
- a radio group

The docs preview uses mocked entry options so the display variants are easy to review without a live element query.

## Related pages

- [Categories](/browser/ui-reference/fields/categories)
- [Checkboxes](/browser/ui-reference/fields/checkboxes)
- [Radio](/browser/ui-reference/fields/radio)
- [Recipients](/browser/ui-reference/fields/recipients)
