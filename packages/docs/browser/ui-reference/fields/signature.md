# Signature

Signature is the field for drawing a signature on a canvas and storing it in a hidden input.

Use this page to preserve the canvas, hidden input, and clear action used by the signature module.

## Preview

<FormiePreview src="../examples/signature.preview.ts" />

## Attributes

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `canvas[data-formie-signature-canvas]` | SignaturePad drawing surface | Required |
| `input[data-formie-signature-input]` | Hidden transport input for the signature value | Required |
| `[data-formie-signature-clear]` | Clear-button selector | Recommended |

## Behavior

The `signature` module:

- mounts `SignaturePad` onto the canvas
- synchronizes the drawn image back into the hidden input as a data URL
- responds to page navigation and resize changes so the canvas can be remeasured safely

## Events

Signature emits a field event once the drawing surface and `SignaturePad` instance are ready.

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

## Related pages

- [JavaScript events](/browser/behavior/javascript-events)
- [Submission handling](/browser/behavior/submission-handling)
- [CSS variables](/browser/ui-reference/css-variables)
