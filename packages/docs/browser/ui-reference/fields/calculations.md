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

<span id="behavior"></span>

## Behaviour

The `calculations` module:

- resolves live values from referenced fields
- evaluates the configured formula whenever watched source inputs change
- rebinds its watchers when the field tree changes

## Events

Calculations emits field events around each formula evaluation.

#### The `formie:field:calculations:before-evaluate` Event

Triggered right before the formula is evaluated. The event is dispatched on the Calculations field's container, so `event.target.dataset.formieFieldHandle` holds the field handle. For a field inside a Group or Repeater it holds the field's path instead, such as `myGroup.quoteTotal` or `items.0.quoteTotal`. Formula variables are named after the fields they refer to with a `field_` prefix, such as `field_discount`.

```js
document.addEventListener('formie:field:calculations:before-evaluate', (event) => {
  const { variables } = event.detail;

  // Only target the quote total field.
  if (event.target.dataset.formieFieldHandle !== 'quoteTotal') {
    return;
  }

  // Treat a blank discount input as zero before evaluation.
  if (variables.field_discount === '') {
    variables.field_discount = 0;
  }
});
```

#### The `formie:field:calculations:after-evaluate` Event

Triggered right after the formula has been evaluated and before the result is written back into the field.

```js
document.addEventListener('formie:field:calculations:after-evaluate', (event) => {
  // Format numeric results for display without changing the source fields.
  if (typeof event.detail.result === 'number') {
    event.detail.result = event.detail.result.toFixed(2);
  }
});
```

## Related Pages

- [Summary](/browser/ui-reference/fields/summary)
- [JavaScript events](/browser/behavior/javascript-events)
- [Submission handling](/browser/behavior/submission-handling)
