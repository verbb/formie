# Table

Table is the field for entering several related values in repeatable rows and columns.

Use this page to preserve the table body, row, template, and add/remove attributes used for dynamic rows.

## Preview

<FormiePreview src="../examples/table.preview.ts" />

## Attributes

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-table-field-layout` | Table field selector | Required |
| `data-formie-table` | Table wrapper selector | Required |
| `data-formie-table-body` | Live row container | Required |
| `data-formie-table-row` | Individual row selector | Required |
| `data-formie-table-add` | Add-row button hook | Required for dynamic tables |
| `data-formie-table-remove` | Remove-row button hook | Required for dynamic tables |
| `data-formie-table-template` with `__ROW__` placeholders | Template source for new rows | Required for dynamic tables |

## Behavior

The `table` module:

- appends rows from the configured template
- removes rows while respecting minimum row counts
- disables add-row actions when the configured maximum has been reached

## Events

Table emits field events as rows are prepared, appended, and removed.

#### The `formie:field:table:init` event

Triggered after the table field has been wired and its existing rows are ready.

```js
document.addEventListener('formie:field:table:init', (event) => {
  const { table } = event.detail;

  // Only target the links table field.
  if (table?.dataset.formieFieldHandle !== 'links') {
    return;
  }

  // Mirror the current row count onto the table wrapper.
  table.dataset.rowCount = String(table.querySelectorAll('[data-formie-table-row]').length);
});
```

#### The `formie:field:table:append` event

Triggered after a new row has been appended from the configured template.

```js
document.addEventListener('formie:field:table:append', (event) => {
  const { table, row } = event.detail;

  // Only target the links table field.
  if (table?.dataset.formieFieldHandle !== 'links') {
    return;
  }

  // Move focus into the first input in the new row.
  row.querySelector('input')?.focus();
});
```

#### The `formie:field:table:remove` event

Triggered after an existing row has been removed.

```js
document.addEventListener('formie:field:table:remove', (event) => {
  const { table } = event.detail;

  // Only target the links table field.
  if (table?.dataset.formieFieldHandle !== 'links') {
    return;
  }

  // Toggle a state attribute once all rows have been removed.
  table.toggleAttribute('data-table-empty', table.querySelectorAll('[data-formie-table-row]').length === 0);
});
```

## Related pages

- [Repeater](/browser/ui-reference/fields/repeater)
- [JavaScript events](/browser/behavior/javascript-events)
- [Submission handling](/browser/behavior/submission-handling)
