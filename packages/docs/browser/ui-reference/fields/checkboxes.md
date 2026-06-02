# Checkboxes

Checkboxes is the multi-select choice field for a group of options.

Use this page to preserve the required attributes, empty-value handling, and option markup used by the checkbox-radio module.

## Preview

<FormiePreview src="../examples/checkboxes.preview.ts" />

## Attributes

Checkbox groups depend on these structural attributes:

### Field

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-field-handle` | Stable field identity used by validation, conditions, and error rendering | Required |
| `data-formie-max-options` | Max-selection enforcement | Optional |

### Field layout

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-checkboxes-field-layout` | Group selector used by the checkbox-radio module | Required for checkbox behavior |

### Hidden input

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `name` fallback input | Ensures the field still submits when nothing is checked | Recommended |

### Field options

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-checkboxes-options` | Option-group wrapper | Required for checkbox behavior |

### Field input

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-checkbox-input` | Checkbox selector used by Formie | Required for checkbox behavior |
| `data-formie-input-id` | Stable option identity | Recommended |
| `data-checkbox-toggle` | Optional "toggle all" checkbox hook | Preserve when using the built-in toggle option |

## Styling classes

These classes are for presentation only. They are not behavior requirements:

### Field layout

| Class | Description |
| --- | --- |
| `formie-checkboxes-field-layout` | Checkbox group layout styling class |
| `formie-checkboxes-field-label` | Checkbox group label styling class |

### Field options

| Class | Description |
| --- | --- |
| `formie-field-options` | Shared option-group styling |
| `formie-checkboxes-options` | Checkbox group options styling class |
| `formie-field-option` | Shared option wrapper styling |
| `formie-checkbox-option` | Checkbox option wrapper styling class |

### Field input

| Class | Description |
| --- | --- |
| `formie-input` | Shared checkbox styling and focus treatment |
| `formie-checkbox-input` | Checkbox input styling class |
| `formie-input-error` | Error-state styling class |

### Field option label

| Class | Description |
| --- | --- |
| `formie-field-option-label` | Shared option label styling |
| `formie-checkbox-option-label` | Checkbox option label styling class |

## Behavior

The checkbox-radio module keeps checkbox fields aligned with Formie semantics by:

- synchronizing DOM `checked` attributes with live state
- treating required checkboxes as one logical group instead of per-input native validation
- enforcing `data-formie-max-options` by disabling only unchecked options after the limit is reached

## Events

Checkbox fields use the shared checkbox-radio field events described on [JavaScript events](/browser/behavior/javascript-events).

#### The `formie:field:checkbox-radio:init` event

Triggered after the checkbox group has been wired and max-option or required behavior is active.

```js
document.addEventListener('formie:field:checkbox-radio:init', (event) => {
  const { checkboxRadio } = event.detail;

  // Only target the services field.
  if (checkboxRadio?.dataset.formieFieldHandle !== 'services') {
    return;
  }

  // Mark the group so custom UI can react once behavior is ready.
  checkboxRadio.setAttribute('data-checkboxes-ready', 'true');
});
```

## Related pages

- [Radio](/browser/ui-reference/fields/radio)
- [Agree](/browser/ui-reference/fields/agree)
- [JavaScript events](/browser/behavior/javascript-events)
