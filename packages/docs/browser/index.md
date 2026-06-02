# Browser

The Browser docs cover the front-end JavaScript and CSS used by Formie’s rendered forms.

If you are coming from the Formie plugin docs, this is where to look when you want to understand or customize what happens in the browser: JavaScript events, submit handling, validation, field modules, rendered markup, CSS variables, and the default theme CSS.

There are two common paths:

- **Plugin-rendered forms** - Craft renders the form HTML and Formie’s normal front-end assets enhance it. Use these docs to find events, CSS hooks, module behavior, and markup contracts.
- **Your own frontend bundle** - You import `@verbb/formie-browser`, decide when forms mount, and choose whether to load Formie’s CSS or provide your own. Use this path when you disable Formie’s automatic JavaScript, load forms from an endpoint, or need lower-level client control.

If React, Vue, or Web Components owns the form surface, use those package docs instead. They build on the same front-end concepts, but the integration point is the framework component rather than a Craft-rendered form already on the page.

## Installation

You only need to install `@verbb/formie-browser` when your own build imports the package. Plugin-rendered forms can use the assets emitted by Formie without installing the npm package in your project.

```bash
npm install @verbb/formie-browser
```

Import the shipped CSS if you want the default Formie UI:

```ts
import '@verbb/formie-browser/css/formie.css';
```

## If Formie already renders the form

For most Craft-rendered forms, Formie outputs the markup, startup script, translations, modules, and CSS links needed for the front end. You do not need to recreate that setup from npm just to listen for events or adjust styling.

Start here when you want to customize what the plugin already provides:

- [JavaScript events](/browser/behavior/javascript-events) for lifecycle, page navigation, validation, and submit events.
- [Submission handling](/browser/behavior/submission-handling) for Ajax results and submit-flow behavior.
- [UI reference](/browser/ui-reference/) for rendered markup, data attributes, and class hooks.
- [CSS variables](/browser/ui-reference/css-variables) for changing the default theme without replacing templates.
- [Modules](/browser/modules/) for field, captcha, address, and payment behavior.

If you are moving old event listeners or custom scripts from older plugin-rendered forms, see [Migrating from Formie Plugin](/browser/behavior/migrating-from-formie-plugin).

## If your bundle initializes forms

Use `@verbb/formie-browser` when Formie still owns the rendered HTML and browser behavior, but your frontend bundle owns mounting.

Start with `formie({ element })`. It wraps the lower-level browser client with DOM-ready handling, mounts the matched form elements, and gives you simple success and error hooks.

```ts
import { formie } from '@verbb/formie-browser';
import '@verbb/formie-browser/css/formie.css';

await formie({
  element: '#contact-form',
  onReady(instance) {
    console.log('Mounted:', instance.id);
  },
  onSuccess(result) {
    console.log('Submit ok:', result);
  },
  onError(result) {
    console.log('Submit failed:', result);
  },
});
```

`element` can be:

- a CSS selector such as `'#contact-form'`
- one DOM element
- any iterable collection of elements

When you pass a selector, Formie waits until the DOM is ready before it gives up on finding the target.

If Craft still renders the form HTML but you want to control startup timing, use [Manual initialization](/browser/behavior/manual-initialization). If you need custom module registration, explicit mount/unmount control, or endpoint-driven loading, use [Custom client](/browser/behavior/custom-client).

## Options

`formie()` supports these options:

| Name | Type | Required | Description |
| --- | --- | --- | --- |
| `element` | `string \| Element \| Iterable<Element>` | Yes | DOM target or selector to mount. |
| `transport` | `'rest' \| 'graphql'` | No | Use when Formie should fetch HTML payloads itself instead of only enhancing existing rendered markup. |
| `endpoint` | `string` | No | Craft site base URL for REST, or GraphQL endpoint for GraphQL. |
| `formHandle` | `string` | No | Form handle to load when you are not mounting an already rendered form. |
| `payload` | `FormEndpointPayload` | No | Preloaded HTML payload for explicit mounting. |
| `refreshTokens` | `boolean` | No | Refresh CSRF, request, render, and captcha tokens as needed. |
| `locale` | `string` | No | Advanced locale override. |
| `siteId` | `number` | No | Request a specific site. |
| `autoVisible` | `boolean` | No | Mount eagerly instead of waiting for visibility-driven initialization. |
| `theme` | `'formie' \| 'none'` | No | Use the shipped Formie theme or skip it. |
| `themeConfig` | `Record<string, unknown>` | No | Additional browser-theme configuration. |
| `observe` | `boolean` | No | Start low-level DOM observation after the initial mount. |
| `onReady` | `function` | No | Called after each matched form instance mounts. |
| `onResult` | `function` | No | Called for every submit result. |
| `onSuccess` | `function` | No | Called when a submit result succeeds. |
| `onError` | `function` | No | Called when a submit result fails. |
| `onEvent` | `function` | No | Called for browser lifecycle events exposed by the mounted instance. |

## Return value

`formie()` resolves to a small app handle:

| Name | Type | Description |
| --- | --- | --- |
| `client` | `FormieClient` | The underlying advanced browser client. |
| `instances` | `FormieFormInstance[]` | Mounted instances currently tracked by the helper. |
| `get(target)` | `function` | Returns one mounted instance by selector or element. |
| `rescan()` | `function` | Re-runs the `element` lookup and mounts any new matches. |
| `destroy()` | `function` | Unsubscribes listeners, stops observation, and unmounts tracked forms. |

## Existing rendered forms

If Craft already rendered the final form HTML into the page and you are initializing from your own bundle, `formie()` is usually all you need:

```html
<form id="contact-form" data-formie-form>
  ...
</form>
```

```ts
await formie({
  element: '#contact-form',
});
```

On plugin-rendered pages, Craft can also output Formie's startup script and an inline JSON translations seed for you. `formie()` is for the cases where you want to take over mounting timing from your own bundle, not for re-creating the whole plugin startup layer.

## When you need the advanced client

Use the lower-level browser client only when you need a real story for it, such as:

- custom module registration
- explicit `mount()` / `unmount()` / `update()` control
- manual `scan()` / `observe()` coordination in a larger app shell
- migration code that already depends on the full browser client surface

That path lives on [Custom client](/browser/behavior/custom-client).

If Formie is still outputting its browser script for you and you only need to take over boot timing, use [Manual initialization](/browser/behavior/manual-initialization) instead.

## Related pages

- [JavaScript events](/browser/behavior/javascript-events)
- [Submission handling](/browser/behavior/submission-handling)
- [Manual initialization](/browser/behavior/manual-initialization)
- [Migrating from Formie Plugin](/browser/behavior/migrating-from-formie-plugin)
- [Validation](/browser/validation/)
- [Modules](/browser/modules/)
- [UI reference](/browser/ui-reference/)
