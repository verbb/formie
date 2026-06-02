# Hidden

Hidden is the field for storing values without rendering visible UI.

Use this page when you need to preserve the hidden-field markup used for prefills, tracking values, or cookie-backed state.

## Preview

<FormiePreview src="../examples/hidden.preview.ts" />

## Attributes

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-field-type="hidden"` | Hidden field container marker | Recommended |
| `input[data-formie-hidden-input]` | Hidden module selector | Required |
| `name` | Submission payload key | Required |
| `value` | Server-rendered or browser-populated hidden value | Required |

## Behavior

The `hidden` module can populate hidden fields from declarative options such as a configured cookie name. It emits `formie:module:hidden:init` and `formie:module:hidden:destroy` at the module level.

## Related pages

- [JavaScript events](/browser/behavior/javascript-events)
- [Submission handling](/browser/behavior/submission-handling)
