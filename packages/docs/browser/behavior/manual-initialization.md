# Manual initialization

Manual initialization is for cases where Formie is still rendering the form HTML for you, but you do not want Formie's startup script to auto-start that form for you.

## Manual initialization use cases

On a normal Craft-rendered page, Formie's startup script reads its page-level settings from the emitted script tag, scans the page for roots such as `[data-formie]` and `[data-formie-form]`, mounts them automatically, and can keep observing for later DOM inserts.

Once mounted, Formie handles the normal browser layer for you, including:

- client-side validation
- multi-page navigation and progress state
- conditional logic and field modules
- file upload, captcha, and payment module setup
- browser events such as `formie:mount:after` and `formie:submit:result`

This page covers the cases where you want to keep Formie-rendered HTML, disable automatic initialization, and then initialize those forms from your own frontend bundle when your page lifecycle says it is ready.

## Disable auto-init

Disable automatic startup on the rendered form in Twig:

```twig
{{ craft.formie.renderForm(form, {
  initJs: false,
}) }}
```

`initJs` is form-scoped. It marks that rendered form as opt-out, but it does not turn off the startup script for the whole page.

## Initialization

Import `formie()` from `@verbb/formie-browser` and target the rendered form root once your own bundle is ready:

```ts
import { formie } from '@verbb/formie-browser';
import '@verbb/formie-browser/css/formie.css';

await formie({
  element: '[data-formie-form]',
});
```

You can also initialize only part of the page:

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
{{ craft.formie.frontendAssets({
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

Plugin-rendered pages also seed browser translations for you through an inline JSON script tag, so validation and UI messages still work when your own bundle calls `formie()`.

This is helpful when:

- your page keeps appending content over time
- a CMS or UI library keeps injecting fragments after initial boot

If your integration has explicit transition hooks and you want deterministic control, stop observation before swaps and restart it with `rescan()` or a fresh `formie()` mount when the new DOM is ready.

## Hidden and modal forms

By default, Formie mounts roots when they become visible. That is usually what you want for drawers, tabs, accordions, and modals.

If a specific form should mount immediately instead of waiting until it becomes visible, set:

```html
<form data-formie-form data-formie-auto-visible="false">
  ...
</form>
```

Use that sparingly, because eager mounts can initialize heavier providers before the user can interact with them.

## Advanced manual mounting

If you need lower-level control than `formie()` gives you, the JavaScript API also exposes `mount()`, `unmount()`, `update()`, `scan()`, and `observe()`.

Those methods are covered on [Custom client](/browser/behavior/custom-client).

## Browser-package boundary

This page assumes Formie still owns the rendered form and its browser behavior. If your framework owns the render tree, component lifecycle, and routing model, the framework package docs are usually a better fit than stretching the browser package beyond its intended boundary.

Craft-first integrations such as Sprig should still be documented primarily in the main Formie plugin docs and linked here only when needed.

## Related pages

- [Custom client](/browser/behavior/custom-client)
- [Modules](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
- [Submission handling](/browser/behavior/submission-handling)
