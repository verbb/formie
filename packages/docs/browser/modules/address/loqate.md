# Loqate

Loqate is an address provider module for provider-backed address lookup and sub-field population.

## Events

Loqate does not currently have a dedicated address-provider event documented on the main events page, so the shared module lifecycle is the primary public hook.

#### The `formie:module:loqate:after-setup` event

Triggered after the Loqate module has finished setup for its target.

```js
document.addEventListener('formie:module:loqate:after-setup', (event) => {
  // Useful when you need to confirm the provider has mounted.
  console.log('Loqate module ready:', event.detail.target);
});
```

## Related pages

- [Address field](/browser/ui-reference/fields/address)
- [Overview](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
