# Phone

Phone is the phone number field. It can render as a basic tel input or use the `phone-country` module for country selection and validation.

Use this page to preserve the input attributes and, when the country setting is enabled, the hidden country input that keeps the submit payload aligned.

## Preview

<FormiePreview src="../examples/phone.preview.ts" />

## Attributes

Phone fields can include both a visible tel input and, when country selection is enabled, a hidden country input.

### Field

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-field-handle` | Stable field identity used by validation and error rendering | Required |

### Field input

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `input[type="tel"][data-formie-phone-input]` | Visible phone input selector | Required for phone-country enhancement |
| `data-formie-input` | Generic Formie input marker included in normal output | Recommended |
| `data-formie-input-id` | Stable visible-input identity | Recommended |

### Country input

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `input[data-formie-phone-country-input]` | Country-code storage input kept in sync by the module | Required when country setting is enabled |

## Styling classes

These classes are for presentation only. They are not behavior requirements:

### Field input

| Class | Description |
| --- | --- |
| `formie-input` | Shared tel-input styling and focus treatment |
| `formie-input-error` | Error-state styling class |

## Behavior

The `phone-country` module:

- mounts `intl-tel-input` onto the visible tel input
- keeps the hidden country input synchronized with the selected ISO country code
- validates international numbers through the form validator layer

## Events

Phone-country-enhanced fields emit field events in addition to the broader events documented on [JavaScript events](/browser/behavior/javascript-events).

#### The `formie:field:phone-country:before-init` event

Triggered before `intl-tel-input` is mounted. Use this to change the plugin options before initialization.

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

  // Only proceed when the phone input has been enhanced successfully.
  if (!(phoneCountry instanceof HTMLInputElement)) {
    return;
  }

  const selectedCountry = validator.getSelectedCountryData()?.iso2;

  if (!selectedCountry) {
    return;
  }

  // Expose the active country code for custom labels or analytics hooks.
  phoneCountry.closest('[data-formie-field]')?.setAttribute('data-phone-country', selectedCountry.toUpperCase());
});
```

## Related pages

- [JavaScript events](/browser/behavior/javascript-events)
- [Submission handling](/browser/behavior/submission-handling)
- [CSS variables](/browser/ui-reference/css-variables)
