# Calculations

Calculations evaluates a formula from other field values and writes the result into a read-only input.

## Events

#### The `formie:field:calculations:before-evaluate` event

Triggered right before the formula is evaluated.

```js
document.addEventListener('formie:field:calculations:before-evaluate', (event) => {
  const { calculations, variables } = event.detail;

  // Treat a blank discount field as zero before the formula runs.
  if (calculations?.dataset.formieInputId === 'quoteTotal' && variables.discount === '') {
    variables.discount = 0;
  }
});
```

#### The `formie:field:calculations:after-evaluate` event

Triggered after the formula is evaluated and before the result is written back into the input.

```js
document.addEventListener('formie:field:calculations:after-evaluate', (event) => {
  // Format numeric results for display without changing the source fields.
  if (typeof event.detail.result === 'number') {
    event.detail.result = event.detail.result.toFixed(2);
  }
});
```

The broader module lifecycle also includes scoped events such as `formie:module:calculations:after-setup`.

## Related pages

- [Calculations field](/browser/ui-reference/fields/calculations)
- [Overview](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
