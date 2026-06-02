# Signature

Signature mounts a drawing surface, keeps a hidden input synchronized, and responds to resize or page-navigation changes.

## Events

#### The `formie:field:signature:init` event

Triggered after the drawing surface and `SignaturePad` instance have been created.

```js
let projectSignaturePad;

document.addEventListener('formie:field:signature:init', (event) => {
  // Store the SignaturePad instance for later use elsewhere on the page.
  projectSignaturePad = event.detail.signature;
});

document.querySelector('[data-clear-project-signature]')?.addEventListener('click', () => {
  // Clear the signature from outside the field's built-in button.
  projectSignaturePad?.clear();
});
```

The shared module lifecycle also exposes scoped events such as `formie:module:signature:after-setup`.

## Related pages

- [Signature field](/browser/ui-reference/fields/signature)
- [Overview](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
