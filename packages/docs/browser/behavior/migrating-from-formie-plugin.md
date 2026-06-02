# Migrating from Formie Plugin

This page is for projects moving from the JavaScript bundled with older Formie plugin releases to `@verbb/formie-browser`. The package is new for Formie 4, but it still includes a compatibility bridge so older browser integrations can move over in stages instead of rewriting every listener at once.

Use the bridge only as a migration step. The long-term target is Formie 4's canonical `formie:*` event surface.

## What changed

The main browser changes from the old bundled Formie plugin JavaScript are:

- lifecycle and submit hooks now use canonical `formie:*` DOM events
- validator lifecycle hooks now use canonical `formie:validator:*` events
- the public browser API is now either the `formie()` helper or a client created with `createFormieClient()`

If your old code only listens for legacy event names, turn on compatibility while you migrate each listener to the new event surface.

## Turn on compatibility

### Managed or rendered forms

If you are mounting from rendered HTML, enable compatibility on the root:

```html
<form data-formie data-formie-form data-formie-compatibility="true">
  ...
</form>
```

### Your own bundle

If you mount with the package directly, pass `compatibility` in the mount options:

```ts
import { createFormieClient } from '@verbb/formie-browser';

const formie = createFormieClient();
const root = document.querySelector('[data-formie-form]');

if (root instanceof HTMLElement) {
  await formie.mount(root, {
    mode: 'server-rendered',
    compatibility: true,
  });
}
```

You can also opt into only part of the bridge:

```ts
await formie.mount(root, {
  mode: 'server-rendered',
  compatibility: {
    legacyDomEvents: true,
    legacyValidatorEvents: false,
  },
});
```

## Event mapping

These legacy DOM events can be bridged during migration:

| Legacy event | Canonical event | Notes |
| --- | --- | --- |
| `onFormieLoaded` | `formie:mount:after` | Approximate mapping |
| `onFormieInit` | `formie:mount:after` | Approximate mapping |
| `onFormieReady` | `formie:mount:after` | Safe mapping |
| `onAfterFormieSubmit` | `formie:submit:result` | Safe mapping |
| `onFormieSubmitError` | `formie:submit:result` | Safe mapping |
| `onFormiePageToggle` | `formie:page:navigate:after` | Safe mapping |
| `onBeforeFormieSubmit` | `formie:submit:before` | Approximate mapping |
| `onFormieValidate` | `formie:stage:validate:before` | Approximate mapping |
| `onAfterFormieValidate` | `formie:stage:validate:after` | Approximate mapping |
| `onFormieSubmit` | `formie:submit:after` | Approximate mapping |

These validator events can be bridged too:

| Legacy event | Canonical event |
| --- | --- |
| `formieValidatorInitialized` | `formie:validator:ready` |
| `formieValidatorDestroyed` | `formie:validator:destroy` |
| `formieValidatorShowError` | `formie:validator:show-error` |
| `formieValidatorClearError` | `formie:validator:clear-error` |

Approximate mappings exist to keep older integrations alive, not to guarantee identical timing in every edge case.

## Recommended migration path

1. Turn on compatibility.
2. Replace old event listeners with the canonical `formie:*` or `formie:validator:*` names.
3. Test any submit-flow logic that relied on older event timing.
4. Remove compatibility once the old listeners are gone.

## Related pages

- [JavaScript events](/browser/behavior/javascript-events)
- [JavaScript API](/browser/)
- [Custom client](/browser/behavior/custom-client)
