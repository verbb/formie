# reCAPTCHA

reCAPTCHA support is grouped across the Enterprise, v3, v2 Checkbox, and v2 Invisible variants because they share the same provider family and browser behavior.

## Notes

- Choose the module id that matches the exact reCAPTCHA variant configured for the form.

## Events

reCAPTCHA relies on the shared module lifecycle and the broader submit flow documented on [JavaScript events](/browser/behavior/javascript-events).

#### The `formie:module:recaptcha-v3:after-setup` event

Triggered after the active module has finished setup for its target form.

```js
document.addEventListener('formie:module:recaptcha-v3:after-setup', (event) => {
  // Use the same pattern with the other reCAPTCHA module ids when needed.
  console.log('reCAPTCHA ready:', event.detail.target);
});
```

## Related pages

- [Overview](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
- [Submission handling](/browser/behavior/submission-handling)
