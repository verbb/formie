# PlaceKit

PlaceKit is an address provider module that enhances an Address field with provider-driven autocomplete and sub-field population.

## Events

#### The `formie:address:place-kit:before-init` event

Triggered before the PlaceKit provider initializes.

```js
document.addEventListener('formie:address:place-kit:before-init', (event) => {
  // Adjust provider options before the autocomplete instance is created.
  console.log('Before PlaceKit init:', event.detail);
});
```

#### The `formie:address:place-kit:populate` event

Triggered when PlaceKit writes selected address data into the field sub-fields.

```js
document.addEventListener('formie:address:place-kit:populate', (event) => {
  // Inspect the selected address and react after the field has been populated.
  console.log('PlaceKit populated address:', event.detail);
});
```

## Related pages

- [Address field](/browser/ui-reference/fields/address)
- [Overview](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
