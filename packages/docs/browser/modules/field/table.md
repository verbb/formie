# Table

Table manages repeatable rows and columns, template cloning, and add or remove controls inside a table field.

## Events

#### The `formie:field:table:init` event

Triggered after the table field has been wired and its existing rows are ready.

```js
document.addEventListener('formie:field:table:init', (event) => {
  const { table } = event.detail;

  if (table?.dataset.formieFieldHandle === 'links') {
    table.dataset.rowCount = String(table.querySelectorAll('[data-formie-table-row]').length);
  }
});
```

#### The `formie:field:table:append` event

Triggered after a new row has been appended from the configured template.

```js
document.addEventListener('formie:field:table:append', (event) => {
  const { table, row } = event.detail;

  if (table?.dataset.formieFieldHandle === 'links') {
    row.querySelector('input')?.focus();
  }
});
```

#### The `formie:field:table:remove` event

Triggered after an existing row has been removed.

```js
document.addEventListener('formie:field:table:remove', (event) => {
  const { table } = event.detail;

  if (table?.dataset.formieFieldHandle === 'links') {
    table.toggleAttribute('data-table-empty', table.querySelectorAll('[data-formie-table-row]').length === 0);
  }
});
```

## Related pages

- [Table field](/browser/ui-reference/fields/table)
- [Overview](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
