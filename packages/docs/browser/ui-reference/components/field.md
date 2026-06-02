# Field

Use this page when you are overriding Formie's shared field wrapper structure instead of only changing one field type's inner markup.

## Anatomy

### Normal render

<FormiePreview src="../examples/field-normal.preview.ts" />

### Annotated render

<FormiePreview src="../examples/field-anatomy.preview.ts" />

Most field pages assume this shared wrapper structure still exists around the field-specific input markup:

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-field` | Shared field wrapper hook | Required |
| `data-formie-field-handle` | Stable field identity for browser targeting | Required |
| `data-formie-field-uid` | Stable field instance identity | Preserve when present |
| `data-formie-field-type` | Field type identity for modules and styling | Preserve when present |
| `data-formie-input-id` | Stable id used to coordinate label, errors, and browser helpers | Preserve when present |
| `data-formie-field-has-error` | Browser error-state flag | Preserve when present |
| `data-formie-conditions` | Serialized condition contract | Required when the field has conditions |
| `data-formie-validation` | Serialized validation rule contract | Required when the field validates client-side |

These shared structural nodes matter too:

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-field-layout` | Shared field layout wrapper | Required |
| `data-formie-label-position` | Label placement state | Preserve when present |
| `data-formie-instructions-position` | Instructions placement state | Preserve when present |
| `data-formie-label` / `data-formie-field-label` | Label hooks | Required when the field has a label |
| `data-formie-instructions` | Instructions container | Preserve when instructions are shown |
| `data-formie-field-content` | Inner field content wrapper | Required |
| `data-formie-field-control` | Control wrapper for input-owned browser UI state | Required |
| `data-formie-field-errors` | Errors container | Required for browser error rendering |
| `data-formie-field-error` | Individual error item | Required for browser error rendering |
