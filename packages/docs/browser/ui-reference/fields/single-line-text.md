# Single Line Text

Single Line Text is the standard text input field.

Use this page to see the default markup, preserve the required attributes when overriding templates, and identify the state attributes that matter for text inputs.

## Preview

<FormiePreview src="../examples/single-line-text.preview.ts" />

## Attributes

Single Line Text fields span an outer field wrapper plus the actual `<input>`.

### Field

| Attribute | Description | Importance |
| --- | --- | --- |
| `data-formie-field-handle` | Stable field identity used by validation, conditions, calculations, and error rendering | Required |
| `data-formie-field-type="single-line-text"` | Field-type marker on the outer wrapper | Recommended |

### Field input

| Attribute | Description | Importance |
| --- | --- | --- |
| `name` | Submission payload key for the field value | Required |
| `data-formie-input` | Generic input marker included in normal Formie output | Recommended |
| `data-formie-input-id` | Stable input identity for browser behavior and module targeting | Recommended |
| `data-formie-single-line-text-input` | Text-input selector used by text-specific enhancements such as `text-limit` | Required for text-specific modules |
| `data-formie-input-type="text"` | Server-rendered input-type metadata | Recommended |
| `data-formie-required-message` | Custom required-rule message payload | Optional |
| `data-formie-input-error-state` | Initial server-rendered error state | Optional |
| `aria-describedby` | Connects instructions and errors for accessibility | Recommended |

## Styling classes

These classes are for presentation only. They are not behavior requirements:

### Field input

| Class | Description |
| --- | --- |
| `formie-input` | Base input styling and focus treatment |
| `formie-single-line-text-input` | Single Line Text styling class |
| `formie-input-error` | Error-state styling class |

## Accessibility notes

- Inputs with errors should receive `aria-invalid="true"`.
- Error rendering should keep `aria-describedby` references stable.
- Required and error state should be conveyed visually and programmatically.

## Related pages

- [Fields](/browser/ui-reference/fields/)
- [JavaScript events](/browser/behavior/javascript-events)
- [CSS variables](/browser/ui-reference/css-variables)
