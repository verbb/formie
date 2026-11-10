<span id="manual-initialization"></span>

# Manual Initialisation

Manual initialisation is for cases where Formie is still rendering the form HTML for you, but you do not want Formie's startup script to auto-start that form for you.

<span id="manual-initialization-use-cases"></span>

## Manual Initialisation Use Cases

On a normal Craft-rendered page, Formie's startup script reads its page-level settings from the emitted script tag, scans the page for roots such as `[data-formie]` and `[data-formie-form]`, mounts them automatically, and can keep observing for later DOM inserts.

Once mounted, Formie handles the normal browser layer for you, including:

- client-side validation
- multi-page navigation and progress state
- conditional logic and field modules
- file upload, captcha, and payment module setup
- browser events such as `formie:mount:after` and `formie:submit:result`

This page covers the cases where you want to keep server-rendered HTML, disable automatic initialisation, and then initialise those forms from your own browser bundle when your page lifecycle says it is ready.

## Disable Auto-Init

Disable automatic startup on the rendered form in Twig:

```twig
{{ craft.formie.renderForm(form, {
  initJs: false,
}) }}
```

`initJs` is form-scoped. It marks that rendered form as opt-out, but it does not turn off the startup script for the whole page.

<span id="initialization"></span>

## Initialisation

Import `formie()` from `@verbb/formie-browser` and target the rendered form root once your own bundle is ready:

```ts
import { formie } from '@verbb/formie-browser';
import '@verbb/formie-browser/css/formie.css';

await formie({
  element: '[data-formie-form]',
});
```

You can also initialise only part of the page:

```ts
import { formie } from '@verbb/formie-browser';

const container = document.querySelector('#page-content');

if (container) {
  await formie({
    element: container.querySelectorAll('[data-formie-form]'),
  });
}
```

If you want instance access directly, keep the returned app handle:

```ts
const app = await formie({
  element: '#contact-form',
});

console.log(app.get('#contact-form')?.id);
```

## Observation

By default, Formie's startup script only keeps observing the page when `useObserver` is enabled on the asset-rendering side.

If you want to take full ownership in your own bundle, leave `initJs: false` on the form render, turn page-level observation off where you output the startup script, and then opt into observation explicitly when you call `formie()`:

```twig
{{ craft.formie.browserAssets({
  useObserver: false,
}) }}
```

```ts
await formie({
  element: '[data-formie-form]',
  observe: true,
  allowEmpty: true,
});
```

Server-rendered pages also seed browser translations for you through an inline JSON script tag, so validation and UI messages still work when your own bundle calls `formie()`.

This is helpful when:

- your page keeps appending content over time
- a CMS or UI library keeps injecting fragments after initial boot

If your integration has explicit transition hooks and you want deterministic control, stop observation before swaps and restart it with `rescan()` or a fresh `formie()` mount when the new DOM is ready.

## Hidden and Modal Forms

By default, Formie mounts roots when they become visible. That is usually what you want for drawers, tabs, accordions, and modals.

If a specific form should mount immediately instead of waiting until it becomes visible, set:

```html
<form data-formie-form data-formie-auto-visible="false">
  ...
</form>
```

Use that sparingly, because eager mounts can initialise heavier providers before the user can interact with them.

## Advanced Manual Mounting

If you need lower-level control than `formie()` gives you, the JavaScript API also exposes `mount()`, `unmount()`, `update()`, `scan()`, and `observe()`.

Those methods are covered on [Custom client](/browser/behavior/custom-client).

## Browser-Package Boundary

This page assumes Formie still owns the rendered form and its browser behaviour. If your framework owns the render tree, component lifecycle, and routing model, the framework package docs are usually a better fit than stretching the browser package beyond its intended boundary.

Craft-first integrations such as Sprig should still be documented primarily in the main Formie plugin docs and linked here only when needed.

## Craft Action Forms

For a single-page form that posts directly to a Craft action, such as `users/login`, use [Craft Forms](https://verbb.io/craft-plugins/formie/docs/templates/craft-forms). The Twig helper `craft.formie.craftFormAttributes(form)` supplies the browser configuration, and the plugin assets initialize validation automatically.

If your application owns startup, load the assets with `autoInit: false` and initialize marked Craft forms after their HTML is present:

```ts
import { craftForms } from '@verbb/formie-browser';

const app = craftForms({ root: document, observe: true });

// Inspect or extend the validator for a particular form.
const login = document.querySelector<HTMLFormElement>('form[data-formie-craft-form]');
const validator = login ? app.get(login) : null;

// Without observation, call this after inserting or removing forms.
app.rescan();

// Call when the owning page or component is removed.
app.destroy();
```

`craftForms()` is synchronous. `root` defaults to `document`, and `observe` defaults to `false`. It discovers forms marked with `data-formie-craft-form`, skipping `data-formie-init="false"`. Remove that opt-out attribute before calling `rescan()` when using deferred initialization. Repeated scans reuse the validator, and teardown removes listeners and restores the form’s original native-validation setting. When observation is enabled, inserted forms mount and removed forms are cleaned up automatically.

This API uses the existing validator, translations and configured error classes. A valid submit proceeds through the browser with its original action and submitter. It does not load Formie submission modules, refresh Formie tokens or emit submission-success events; the Craft action owns the result. The form emits `formie:validator:ready` with the validator and its `addValidator` and `removeValidator` methods when it is initialized.


## Related Pages

- [Custom client](/browser/behavior/custom-client)
- [Modules](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
- [Submission handling](/browser/behavior/submission-handling)
