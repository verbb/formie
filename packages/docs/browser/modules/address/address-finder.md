# Address Finder

Address Finder is an address provider module that looks up addresses and maps the selected result back into Formie address sub-fields.

## Events

#### The `formie:address:address-finder:populate` event

Triggered when Address Finder writes selected address data into the field sub-fields.

```js
document.addEventListener('formie:address:address-finder:populate', (event) => {
  // Inspect the selected address and react after the field has been populated.
  console.log('Address Finder populated address:', event.detail);
});
```

The shared module lifecycle also exposes scoped events such as `formie:module:address-finder:after-setup`.

## Related pages

- [Address field](/browser/ui-reference/fields/address)
- [Overview](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
