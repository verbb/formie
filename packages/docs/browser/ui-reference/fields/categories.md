# Categories

Categories is an element field that can render as standard choice inputs.

Use this page as the reference for the display variants you need to preserve when mocking or restyling category selection.

## Preview

<FormiePreview src="../examples/categories.preview.ts" />

## Attributes

Categories keeps its own field identity, but the visible control inherits the attributes of the chosen display type:

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-field-type="categories"` | Field identity marker on the outer wrapper | Required |
| `data-formie-input` and `data-formie-input-id` | Shared input identity hooks from the rendered choice control | Required |
| `<select>` / `data-formie-checkbox-input` / `data-formie-radio-input` | Control-level hooks inherited from the selected display type | Required |
| Hidden empty input for checkbox display | Preserves empty-state submission behavior | Recommended for checkbox display |

## Display types

Categories can render as:

- a single-select dropdown
- a multi-select dropdown
- a checkbox group
- a radio group

The docs preview uses mocked category options so the display variants are easy to audit without depending on a live element query.

## Related pages

- [Entries](/browser/ui-reference/fields/entries)
- [Checkboxes](/browser/ui-reference/fields/checkboxes)
- [Radio](/browser/ui-reference/fields/radio)
- [Recipients](/browser/ui-reference/fields/recipients)
