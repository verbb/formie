# Repeater

Repeater manages nested rows of fields, template cloning, and add or remove actions.

## Events

#### The `formie:field:repeater:init` event

Triggered after the repeater field has been wired and its existing rows are ready.

```js
document.addEventListener('formie:field:repeater:init', (event) => {
  const { repeater } = event.detail;

  if (repeater?.dataset.formieFieldHandle === 'experience') {
    repeater.dataset.rowCount = String(repeater.querySelectorAll('[data-formie-repeater-item]').length);
  }
});
```

#### The `formie:field:repeater:append` event

Triggered after a new row has been appended from the configured template.

```js
document.addEventListener('formie:field:repeater:append', (event) => {
  const { repeater, row } = event.detail;

  if (repeater?.dataset.formieFieldHandle === 'experience') {
    row.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
  }
});
```

#### The `formie:field:repeater:init-row` event

Triggered after a newly appended row is ready for nested field or module work.

```js
document.addEventListener('formie:field:repeater:init-row', (event) => {
  const { repeater, row } = event.detail;

  if (repeater?.dataset.formieFieldHandle === 'experience') {
    row.querySelector('input')?.focus();
  }
});
```

#### The `formie:field:repeater:remove` event

Triggered after an existing row has been removed.

```js
document.addEventListener('formie:field:repeater:remove', (event) => {
  const { repeater } = event.detail;

  if (repeater?.dataset.formieFieldHandle === 'experience') {
    repeater.toggleAttribute('data-repeater-empty', repeater.querySelectorAll('[data-formie-repeater-item]').length === 0);
  }
});
```

## Related pages

- [Repeater field](/browser/ui-reference/fields/repeater)
- [Overview](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
