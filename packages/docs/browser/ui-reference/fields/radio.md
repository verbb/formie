# Radio

Radio is the single-select choice field for a group of options.

Use this page to preserve the required attributes and option markup used by the checkbox-radio module.

## Preview

<FormiePreview src="../examples/radio.preview.ts" />

## Attributes

Radio fields span a field wrapper, an options container, and one or more visible radio inputs.

### Field

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-field-handle` | Stable field identity used by validation, conditions, and error rendering | Required |

### Field layout

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-radio-field-layout` | Group selector used by the checkbox-radio module | Required for radio behavior |

### Field options

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-radio-options` | Option-group wrapper | Required for radio behavior |

### Field input

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-radio-input` | Radio selector used by Formie | Required for radio behavior |
| Shared `name` across options | Ensures one logical radio group | Required |
| `data-formie-input-id` per option | Stable option identity | Recommended |

## Styling classes

These classes are for presentation only. They are not behavior requirements:

### Field layout

| Class | Description |
| --- | --- |
| `formie-radio-field-layout` | Radio group layout styling class |
| `formie-radio-field-label` | Radio group label styling class |

### Field options

| Class | Description |
| --- | --- |
| `formie-field-options` | Shared option-group styling |
| `formie-radio-options` | Radio group options styling class |
| `formie-field-option` | Shared option wrapper styling |
| `formie-radio-option` | Radio option wrapper styling class |

### Field input

| Class | Description |
| --- | --- |
| `formie-input` | Shared radio styling and focus treatment |
| `formie-radio-input` | Radio input styling class |
| `formie-input-error` | Error-state styling class |

### Field option label

| Class | Description |
| --- | --- |
| `formie-field-option-label` | Shared option label styling |
| `formie-radio-option-label` | Radio option label styling class |

### Other option

When the **Other** option is enabled, Formie renders a dedicated option row with its own theme config tags.

| Theme config tag | Purpose |
| --- | --- |
| `fieldOtherOption` | Wrapper for the entire Other choice row |
| `fieldOtherOptionInput` | The Other radio input |
| `fieldOtherOptionLabel` | The Other option label |
| `fieldOtherOptionText` | The custom text input shown when Other is selected |

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-other-option-row` | Other option row wrapper | Recommended |
| `data-formie-other-option` | Other radio input marker | Required for Other behaviour |
| `data-formie-other-option-text` | Other text input marker | Required for Other behaviour |
| `data-formie-other-option-label` | Other option label marker | Recommended |

| Class | Description |
| --- | --- |
| `formie-other-option` | Other option row styling class |
| `formie-other-option-input` | Other radio input styling class |
| `formie-other-option-label` | Other option label styling class |
| `formie-other-option-text` | Other text input styling class |

## Behavior

The checkbox-radio module:

- synchronizes `checked` attributes after user interaction
- keeps radio groups consistent when the selected option changes

## Events

Radio fields use the shared checkbox-radio field events described on [JavaScript events](/browser/behavior/javascript-events).

#### The `formie:field:checkbox-radio:init` event

Triggered after the radio group has been wired and is ready for interaction.

```js
document.addEventListener('formie:field:checkbox-radio:init', (event) => {
  const { checkboxRadio } = event.detail;

  // Only target the contact preference field.
  if (checkboxRadio?.dataset.formieFieldHandle !== 'contactPreference') {
    return;
  }

  // Run custom setup once the group is ready.
  console.log('Radio group ready:', checkboxRadio);
});
```

## Related pages

- [Checkboxes](/browser/ui-reference/fields/checkboxes)
- [JavaScript events](/browser/behavior/javascript-events)
- [CSS variables](/browser/ui-reference/css-variables)
