# Snaptcha

Snaptcha is a passive captcha module that keeps its hidden token state synchronized without mounting a visible challenge widget.

## Events

Snaptcha relies on the shared module lifecycle and the broader submit flow documented on [JavaScript events](/browser/behavior/javascript-events).

#### The `formie:module:snaptcha:after-setup` event

Triggered after the Snaptcha module has finished setup for its target form.

```js
document.addEventListener('formie:module:snaptcha:after-setup', (event) => {
  // Useful when you need to confirm the provider has mounted.
  console.log('Snaptcha ready:', event.detail.target);
});
```

## Related pages

- [Overview](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
- [Submission handling](/browser/behavior/submission-handling)
