# Agree

Agree is the consent checkbox field used for terms, privacy, and one-line confirmations.

Use this page to preserve the hidden input, checkbox attributes, and consent label structure when you customize the field.

## Preview

<FormiePreview src="../examples/agree.preview.ts" />

## Attributes

Agree fields span a field wrapper, an optional hidden fallback input, and the visible consent checkbox.

### Field

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-field-handle` | Stable field identity used by validation and error rendering | Required |

### Hidden input

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-input-type="agree"` | Empty-state submission fallback | Recommended |

### Field layout

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-agree-field-layout` | Layout marker used by the checkbox-radio module | Required for agree behavior |

### Field input

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-agree-input` | Consent checkbox selector | Required for agree behavior |
| `data-formie-input-type="agree"` | Consent-specific input type marker in the rendered markup | Recommended |

### Field option label

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-agree-option-label` | Consent text label attribute | Recommended |

## Styling classes

These classes are for presentation only. They are not behavior requirements:

### Field

| Class | Description |
| --- | --- |
| `formie-agree-field` | Agree field wrapper styling class |

### Field layout

| Class | Description |
| --- | --- |
| `formie-agree-field-layout` | Agree field layout styling class |
| `formie-agree-field-label` | Agree field label styling class |

### Field options

| Class | Description |
| --- | --- |
| `formie-agree-options` | Agree option group styling class |
| `formie-agree-option` | Agree option wrapper styling class |

### Field input

| Class | Description |
| --- | --- |
| `formie-input` | Shared checkbox styling and focus treatment |
| `formie-checkbox-input` | Shared checkbox input styling class |
| `formie-agree-input` | Agree-specific checkbox styling class |
| `formie-input-error` | Error-state styling class |

### Field option label

| Class | Description |
| --- | --- |
| `formie-field-option-label` | Shared option label styling |
| `formie-checkbox-option-label` | Shared checkbox option label styling |
| `formie-agree-option-label` | Agree-specific option label styling |

## Behavior

Agree fields use the same checkbox-radio browser behavior as checkbox groups, but as a single consent option:

- required handling still applies at the field level
- checked attributes stay synchronized with live state
- shared checkbox/radio lifecycle events still come through `checkbox-radio`

## Events

Agree fields use the same shared checkbox-radio field events described on [JavaScript events](/browser/behavior/javascript-events).

#### The `formie:field:checkbox-radio:init` event

Triggered after the agree field has been wired and its consent checkbox is ready.

```js
document.addEventListener('formie:field:checkbox-radio:init', (event) => {
  const { checkboxRadio } = event.detail;

  // Only target the terms agreement field.
  if (checkboxRadio?.dataset.formieFieldHandle !== 'termsAgreement') {
    return;
  }

  // Mark the field once the consent checkbox is ready.
  checkboxRadio.setAttribute('data-agree-ready', 'true');
});
```

## Related pages

- [Checkboxes](/browser/ui-reference/fields/checkboxes)
- [Radio](/browser/ui-reference/fields/radio)
- [Submission handling](/browser/behavior/submission-handling)
