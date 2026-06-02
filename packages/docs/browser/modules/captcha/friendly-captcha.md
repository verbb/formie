# Friendly Captcha

Friendly Captcha support is provided by two managed captcha modules that share the same general role but use different SDKs and token transport details.

## Notes

- Choose the module id that matches the Friendly Captcha version your form is configured to use.

## Events

Friendly Captcha relies on the shared module lifecycle and the broader submit flow documented on [JavaScript events](/browser/behavior/javascript-events).

#### The `formie:module:friendly-captcha-v2:after-setup` event

Triggered after the Friendly Captcha v2 module has finished setup for its target form.

```js
document.addEventListener('formie:module:friendly-captcha-v2:after-setup', (event) => {
  // Use the same pattern with `friendly-captcha-v1` when that variant is active.
  console.log('Friendly Captcha v2 ready:', event.detail.target);
});
```

## Related pages

- [Overview](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
- [Submission handling](/browser/behavior/submission-handling)
