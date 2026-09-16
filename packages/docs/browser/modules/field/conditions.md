# Conditions

Conditions evaluates visibility and state rules as the form changes, then applies the resulting hide or show behaviour.

## Events

#### The `formie:conditions:evaluated` Event

Triggered after Formie has evaluated conditional logic for a field or form-state change.

Formie itself mirrors hidden state with `data-formie-conditionally-hidden` on affected nodes. If you also want your own project-specific hook, mirror the result into a separate class:

```js
document.addEventListener('formie:conditions:evaluated', (event) => {
  const { node, shouldHide } = event.detail;

  // Add your own project-specific state hook.
  if (node instanceof HTMLElement) {
    node.classList.toggle('is-conditionally-hidden', shouldHide);
  }
});
```

#### The `formie:module:conditions:init` Event

Triggered after the conditions module has initialised and is ready to evaluate rules.

```js
document.addEventListener('formie:module:conditions:init', (event) => {
  // Helpful when you need to know the conditions system is active.
  console.log('Conditions module ready:', event.detail.count);
});
```

The paired destroy event is `formie:module:conditions:destroy`.

## Related Pages

- [Overview](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
- [Manual initialisation](/browser/behavior/manual-initialization)
