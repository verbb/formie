# CAPTCHA.eu

CAPTCHA.eu is a managed captcha provider module for CAPTCHA.eu challenges inside Formie forms.

## Notes

- This provider loads its own external challenge script as part of setup.

## Events

CAPTCHA.eu relies on the shared module lifecycle and the broader submit flow documented on [JavaScript events](/browser/behavior/javascript-events).

#### The `formie:module:captcha-eu:after-setup` event

Triggered after the CAPTCHA.eu module has finished setup for its target form.

```js
document.addEventListener('formie:module:captcha-eu:after-setup', (event) => {
  // Useful when you need to confirm the provider has mounted.
  console.log('CAPTCHA.eu ready:', event.detail.target);
});
```

## Related pages

- [Overview](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
- [Submission handling](/browser/behavior/submission-handling)
