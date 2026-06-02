# Calculations

Calculations is the field that evaluates a formula from other field values and writes the result into a read-only input.

Use this page to preserve the input attributes and understand how referenced fields drive recalculation.

## Preview

<FormiePreview src="../examples/calculations.preview.ts" />

## Attributes

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `input[data-formie-calculation-input]` | Calculations module selector | Required |
| `readonly` | Prevents direct editing of the calculated value | Recommended |
| `data-formie-input` | Generic Formie input marker | Required |
| `data-formie-input-id` | Stable input identity | Required |

## Behavior

The `calculations` module:

- resolves live values from referenced fields
- evaluates the configured formula whenever watched source inputs change
- rebinds its watchers when the field tree changes

## Events

Calculations emits field events around each formula evaluation.

#### The `formie:field:calculations:before-evaluate` event

Triggered right before the formula is evaluated.

```js
document.addEventListener('formie:field:calculations:before-evaluate', (event) => {
  const { calculations, variables } = event.detail;

  // Only target the quote total field.
  if (calculations?.dataset.formieInputId !== 'quoteTotal') {
    return;
  }

  // Treat a blank discount input as zero before evaluation.
  if (variables.discount === '') {
    variables.discount = 0;
  }
});
```

#### The `formie:field:calculations:after-evaluate` event

Triggered right after the formula has been evaluated and before the result is written back into the field.

```js
document.addEventListener('formie:field:calculations:after-evaluate', (event) => {
  // Format numeric results for display without changing the source fields.
  if (typeof event.detail.result === 'number') {
    event.detail.result = event.detail.result.toFixed(2);
  }
});
```

## Related pages

- [Summary](/browser/ui-reference/fields/summary)
- [JavaScript events](/browser/behavior/javascript-events)
- [Submission handling](/browser/behavior/submission-handling)
