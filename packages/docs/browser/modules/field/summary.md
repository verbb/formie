# Summary

Summary waits for the field to become visible, fetches server-rendered summary HTML, and refreshes it as form state changes.

## Events

#### The `formie:field:summary:field-visible` event

Triggered when the summary field is visible enough to start participating in refreshes.

```js
document.addEventListener('formie:field:summary:field-visible', (event) => {
  const { summary } = event.detail;

  if (summary?.dataset.formieFieldHandle === 'reviewSummary') {
    summary.setAttribute('data-summary-visible', 'true');
  }
});
```

#### The `formie:field:summary:fetch-summary` event

Triggered after fresh summary HTML has been fetched for the field.

```js
document.addEventListener('formie:field:summary:fetch-summary', (event) => {
  const { summary } = event.detail;

  if (summary?.dataset.formieFieldHandle === 'reviewSummary') {
    summary.setAttribute('data-summary-loaded', 'true');
  }
});
```

The shared module lifecycle also exposes scoped events such as `formie:module:summary:after-setup`.

## Related pages

- [Summary field](/browser/ui-reference/fields/summary)
- [Overview](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
