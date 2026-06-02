# PayPal

PayPal is a payment provider module used by Formie payment flows.

## Notes

- Payment providers also participate in the generic `formie:payment:authorize:*` events documented on the main events page.

## Events

#### The `formie:payment:provider-authorize:before` event

Triggered before the active payment provider performs its authorization step.

```js
document.addEventListener('formie:payment:provider-authorize:before', (event) => {
  // Filter provider-level work when you only care about one payment provider.
  if (event.detail.provider?.handle === "paypal") {
    console.log('Preparing provider authorization:', event.detail.provider);
  }
});
```

#### The `formie:module:paypal:init` event

Triggered after the provider module has initialized and is ready to manage provider-specific UI.

```js
document.addEventListener('formie:module:paypal:init', (event) => {
  // Useful when you need to know the provider UI has mounted.
  console.log('paypal module init:', event.detail);
});
```

## Related pages

- [Payment field](/browser/ui-reference/fields/payment)
- [Overview](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
