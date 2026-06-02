# Custom client

Use this page only when the default `formie({ element })` helper is not enough.

If you just want to mount one or more rendered Formie forms from your own bundle, go back to [Browser](/browser/) and use `formie()`. This page is for the lower-level `createFormieClient()` escape hatch.

## When this path makes sense

Reach for `createFormieClient()` when you need:

- explicit `mount()`, `unmount()`, or `update()` control
- direct `scan()` and `observe()` lifecycle management
- custom module registration
- migration code that already depends on the full browser client surface

## Create the client

```ts
import { createFormieClient } from '@verbb/formie-browser';
import '@verbb/formie-browser/css/formie.css';

const client = createFormieClient();
```

## Scan the page

If your page already contains normal Formie roots such as `[data-formie]` or `[data-formie-form]`, scan the document:

```ts
await client.scan(document);
```

## Observe later DOM changes

If your app swaps or appends rendered form markup later, start observation too:

```ts
await client.scan(document);

const stopObserving = client.observe(document);
```

Call the returned cleanup function when that observation scope should stop:

```ts
stopObserving();
```

## Mount one target explicitly

If your app wants tighter control, mount one host element yourself:

```ts
const root = document.querySelector('#newsletter-form');

if (root instanceof HTMLElement) {
  await client.mount(root, {
    mode: 'server-rendered',
    transport: 'rest',
    endpoint: 'https://formie.test',
    formHandle: 'newsletter',
    theme: 'formie',
  });
}
```

Useful advanced options include:

- `payload` when your app already has the rendered Formie payload
- `refreshTokens: false` when your app wants to fully own token refresh behavior
- `compatibility` when you are migrating older Formie browser event listeners in stages

## Useful package helpers

The package also exports a few lower-level helpers that are mainly useful in custom integrations.

### Translations

```ts
import { mergeFormieTranslations, t } from '@verbb/formie-browser';

mergeFormieTranslations({
  'The request timed out.': 'The request timed out. Please try again.',
});

console.log(t('The request timed out.'));
```

On plugin-rendered Craft pages, Formie can seed those translations for you through an inline JSON script tag. Reach for `mergeFormieTranslations()` when your own app owns the locale, such as in headless or fully custom bundle setups.

### Event names

```ts
import {
  FORMIE_HTML_EVENT_NAMES,
  getScopedModuleLifecycleEventName,
} from '@verbb/formie-browser';

console.log(FORMIE_HTML_EVENT_NAMES);
console.log(getScopedModuleLifecycleEventName('project-rating', 'after-setup'));
```

### Field-reference helpers

For advanced modules or surrounding UI that need to reason about Formie field keys and posted names, the package also exports helpers such as `buildFieldValueRegistry()`, `fieldKeyToInputName()`, `inputNameToFieldKey()`, and `resolveFieldReferenceLive()`.

## What this path does not use

This package-import path does not use Formie's plugin-driven startup script flow.

When you import `createFormieClient()` directly, you own client creation and lifecycle control from your own application code instead of relying on the plugin-emitted startup script and its automatic mounting behavior.

## When to use Manual initialization instead

Use [Manual initialization](/browser/behavior/manual-initialization) when Formie is still rendering the form HTML for you, but you want to turn off auto-init and initialize those roots from your own frontend bundle.

## Related pages

- [Browser](/browser/)
- [Manual initialization](/browser/behavior/manual-initialization)
- [JavaScript events](/browser/behavior/javascript-events)
- [Migrating from Formie Plugin](/browser/behavior/migrating-from-formie-plugin)
- [Modules](/browser/modules/)
