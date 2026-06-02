# Turnstile

Turnstile is a managed captcha provider module for Cloudflare Turnstile challenges inside Formie forms.

## Notes

- This provider loads its own external challenge script as part of setup.

## Events

Turnstile relies on the shared module lifecycle and the broader submit flow documented on [JavaScript events](/browser/behavior/javascript-events).

#### The `formie:module:turnstile:after-setup` event

Triggered after the Turnstile module has finished setup for its target form.

```js
document.addEventListener('formie:module:turnstile:after-setup', (event) => {
  // Useful when you need to confirm the provider has mounted.
  console.log('Turnstile ready:', event.detail.target);
});
```

## Related pages

- [Overview](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
- [Submission handling](/browser/behavior/submission-handling)
