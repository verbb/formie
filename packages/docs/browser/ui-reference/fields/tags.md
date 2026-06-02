# Tags

Tags is the field for comma-separated or tokenized values.

Use this page to preserve the tag metadata and input markup when you customize the field or restyle it.

## Preview

<FormiePreview src="../examples/tags.preview.ts" />

## Attributes

Tags uses one visible input, but the field still has an outer wrapper for shared validation and error state.

### Field

| Attribute | Description | Importance |
| --- | --- | --- |
| `data-formie-field-handle` | Stable field identity used by validation, conditions, and error rendering | Required |

### Field input

| Attribute | Description | Importance |
| --- | --- | --- |
| `name` | Submission payload key | Required |
| `data-formie-input` | Generic Formie input marker included in normal output | Recommended |
| `data-formie-input-id` | Stable input identity | Recommended |
| `data-formie-tags` | Serialized tag values used to seed the field state | Recommended |

## Styling classes

These classes are for presentation only. They are not behavior requirements:

### Field input

| Class | Description |
| --- | --- |
| `formie-input` | Shared text-input styling and focus treatment |
| `formie-input-error` | Error-state styling class |

## Related pages

- [Single Line Text](/browser/ui-reference/fields/single-line-text)
- [CSS variables](/browser/ui-reference/css-variables)
