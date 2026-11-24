# Calculations

Calculations evaluates a formula from other field values and writes the result into a read-only input.

## Events

#### The `formie:field:calculations:before-evaluate` Event

Triggered right before the formula is evaluated. The event is dispatched on the Calculations field's container, so `event.target.dataset.formieFieldHandle` holds the field handle. For a field inside a Group or Repeater it holds the field's path instead, such as `myGroup.quoteTotal` or `items.0.quoteTotal`. Formula variables are named after the fields they refer to with a `field_` prefix, such as `field_discount`.

```js
document.addEventListener('formie:field:calculations:before-evaluate', (event) => {
  const { variables } = event.detail;

  // Treat a blank discount field as zero before the formula runs.
  if (event.target.dataset.formieFieldHandle === 'quoteTotal' && variables.field_discount === '') {
    variables.field_discount = 0;
  }
});
```

#### The `formie:field:calculations:after-evaluate` Event

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

## Related Pages

- [Calculations field](/browser/ui-reference/fields/calculations)
- [Overview](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
