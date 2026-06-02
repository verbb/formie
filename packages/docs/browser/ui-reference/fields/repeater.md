# Repeater

Repeater is the field for adding and removing nested rows of fields.

Use this page to preserve the container, row, template, and add/remove button attributes used by the repeater module.

## Preview

<FormiePreview src="../examples/repeater.preview.ts" />

## Attributes

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-repeater-field-layout` | Repeater field selector | Required |
| `data-formie-repeater-container` | Live row container | Required |
| `data-formie-repeater-item` | Individual row selector | Required |
| `data-formie-repeater-add` | Add-row button hook | Required |
| `data-formie-repeater-remove` | Remove-row button hook | Required |
| `data-formie-repeater-template` with `__ROW__` placeholders | Template source for appended rows | Required |
| `data-formie-template-id` | Links the field, container, and template together | Required |

## Behavior

The `repeater` module:

- appends new rows from the configured template
- removes rows while respecting configured minimum row counts
- seeds minimum rows through the same append flow used by user interaction

## Events

Repeater emits field events as rows are prepared, appended, initialized, and removed.

#### The `formie:field:repeater:init` event

Triggered after the repeater field has been wired and its existing rows are ready.

```js
document.addEventListener('formie:field:repeater:init', (event) => {
  const { repeater } = event.detail;

  // Only target the experience repeater.
  if (repeater?.dataset.formieFieldHandle !== 'experience') {
    return;
  }

  // Mirror the current row count onto the field wrapper.
  repeater.dataset.rowCount = String(repeater.querySelectorAll('[data-formie-repeater-item]').length);
});
```

#### The `formie:field:repeater:append` event

Triggered after a new row has been appended from the configured template.

```js
document.addEventListener('formie:field:repeater:append', (event) => {
  const { repeater, row } = event.detail;

  // Only target the experience repeater.
  if (repeater?.dataset.formieFieldHandle !== 'experience') {
    return;
  }

  // Bring the newly added row into view for the user.
  row.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
});
```

#### The `formie:field:repeater:init-row` event

Triggered after a newly appended row is ready for nested field or module work.

```js
document.addEventListener('formie:field:repeater:init-row', (event) => {
  const { repeater, row } = event.detail;

  // Only target the experience repeater.
  if (repeater?.dataset.formieFieldHandle !== 'experience') {
    return;
  }

  // Move focus into the first nested input in the new row.
  row.querySelector('input')?.focus();
});
```

#### The `formie:field:repeater:remove` event

Triggered after an existing row has been removed.

```js
document.addEventListener('formie:field:repeater:remove', (event) => {
  const { repeater } = event.detail;

  // Only target the experience repeater.
  if (repeater?.dataset.formieFieldHandle !== 'experience') {
    return;
  }

  // Toggle a state attribute once all rows have been removed.
  repeater.toggleAttribute('data-repeater-empty', repeater.querySelectorAll('[data-formie-repeater-item]').length === 0);
});
```

## Related pages

- [Table](/browser/ui-reference/fields/table)
- [JavaScript events](/browser/behavior/javascript-events)
- [Manual initialization](/browser/behavior/manual-initialization)
