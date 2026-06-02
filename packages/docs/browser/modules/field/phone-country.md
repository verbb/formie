# Phone country

Phone country enhances a tel input with `intl-tel-input`, country selection, and validator integration.

## Events

#### The `formie:field:phone-country:before-init` event

Triggered before `intl-tel-input` is mounted.

```js
document.addEventListener('formie:field:phone-country:before-init', (event) => {
  // Limit the picker to the countries supported by this form.
  event.detail.options.onlyCountries = ['us', 'ca', 'gb'];
});
```

#### The `formie:field:phone-country:init` event

Triggered after the phone input, country synchronization, and validator wiring are ready.

```js
document.addEventListener('formie:field:phone-country:init', (event) => {
  const { phoneCountry, validator } = event.detail;
  const selectedCountry = validator.getSelectedCountryData()?.iso2;

  if (phoneCountry instanceof HTMLInputElement && selectedCountry) {
    phoneCountry.closest('[data-formie-field]')?.setAttribute('data-phone-country', selectedCountry.toUpperCase());
  }
});
```

The shared module lifecycle also exposes scoped events such as `formie:module:phone-country:after-setup`.

## Related pages

- [Phone field](/browser/ui-reference/fields/phone)
- [Overview](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
