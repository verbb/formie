# Opayo

Opayo is a payment provider module used by Formie payment flows.

## Checkout modes

The Opayo integration supports two checkout modes, configured on the integration in **Formie → Settings → Payments**:

- **Own Form** — card fields are rendered on the merchant page and tokenised with `sagepayOwnForm`.
- **Drop-in Checkout** — card fields are rendered inside an Opayo-hosted iframe with `sagepayCheckout`.

Both modes produce the same `opayoTokenId` and `opayoSessionKey` hidden inputs before submit. Server-side payment capture and 3DS handling are unchanged.

## Notes

- Payment providers also participate in the generic `formie:payment:authorize:*` events documented on the main events page.

## Events

#### The `formie:payment:provider-authorize:before` event

Triggered before the active payment provider performs its authorization step.

```js
document.addEventListener('formie:payment:provider-authorize:before', (event) => {
  // Filter provider-level work when you only care about one payment provider.
  if (event.detail.provider?.handle === "opayo") {
    console.log('Preparing provider authorization:', event.detail.provider);
  }
});
```

#### The `formie:module:opayo:init` event

Triggered after the provider module has initialized and is ready to manage provider-specific UI.

```js
document.addEventListener('formie:module:opayo:init', (event) => {
  // Useful when you need to know the provider UI has mounted.
  console.log('opayo module init:', event.detail);
});
```

#### The `formie:payment:opayo:challenge` event

Triggered when opayo requires a provider-specific challenge step before submission can continue.

```js
document.addEventListener('formie:payment:opayo:challenge', (event) => {
  // Handle the provider-specific follow-up step.
  console.log('opayo challenge required:', event.detail);
});
```

## Related pages

- [Payment field](/browser/ui-reference/fields/payment)
- [Overview](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
