# Google address

Google address is an address provider module that uses Google address services to populate Formie address sub-fields.

## Events

#### The `formie:address:google:populate` event

Triggered when Google writes selected address data into the field sub-fields.

```js
document.addEventListener('formie:address:google:populate', (event) => {
  // Inspect the selected address and react after the field has been populated.
  console.log('Google populated address:', event.detail);
});
```

The event namespace uses `google`, while the module id remains `google-address`.

The shared module lifecycle also exposes scoped events such as `formie:module:google-address:after-setup`.

## Related pages

- [Address field](/browser/ui-reference/fields/address)
- [Overview](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
