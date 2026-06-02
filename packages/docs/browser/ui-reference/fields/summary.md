# Summary

Summary is the field that loads and refreshes a summary of the current submission.

Use this page to preserve the summary token and container attributes used by the summary module.

## Preview

<FormiePreview src="../examples/summary.preview.ts" />

## Attributes

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `input[data-formie-summary-token]` | Access token required for summary fetches | Required |
| `[data-formie-summary-container]` | Summary content container | Required |
| `[data-formie-summary-blocks]` | Replaceable summary block wrapper | Required |
| `data-formie-field-type="summary"` | Field identity marker | Recommended |

## Behavior

The `summary` module:

- waits until the field becomes visible before fetching summary HTML
- refreshes after page navigation, submit results, and other field changes
- applies loading state to the summary blocks while requests are in flight

## Events

Summary emits field events as it becomes active and refreshes its rendered content.

#### The `formie:field:summary:field-visible` event

Triggered when the summary field is visible enough to start participating in refreshes.

```js
document.addEventListener('formie:field:summary:field-visible', (event) => {
  const { summary } = event.detail;

  // Only target the review summary field.
  if (summary?.dataset.formieFieldHandle !== 'reviewSummary') {
    return;
  }

  // Expose visibility state for custom loading or analytics UI.
  summary.setAttribute('data-summary-visible', 'true');
});
```

#### The `formie:field:summary:fetch-summary` event

Triggered after fresh summary HTML has been fetched for the field.

```js
document.addEventListener('formie:field:summary:fetch-summary', (event) => {
  const { summary } = event.detail;

  // Only target the review summary field.
  if (summary?.dataset.formieFieldHandle !== 'reviewSummary') {
    return;
  }

  // Mark the field once it has completed its first refresh.
  summary.setAttribute('data-summary-loaded', 'true');
});
```

## Related pages

- [Calculations](/browser/ui-reference/fields/calculations)
- [JavaScript events](/browser/behavior/javascript-events)
- [Submission handling](/browser/behavior/submission-handling)
