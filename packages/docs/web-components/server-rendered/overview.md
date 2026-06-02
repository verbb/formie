# Overview

Choose Web Components server-rendered forms when you want the simplest custom-element integration and you are happy for Formie to keep owning the form UI.

If you want to see Web Components server-rendered forms in a fuller app setup, use the [Web Components starter](https://formie-starters.verbb.io/web-components) as a working example.

In this setup:

- your app owns the custom-element host
- Formie still owns the rendered HTML inside it
- the browser package still owns validation, submit flow, and browser-side behavior

Browser events and browser modules still apply for Web Components server-rendered forms. Use the [Browser](/browser/) docs when you need deeper browser behavior extension points.

## Custom element

`<formie-form>` is the main Web Components entry point.

Most authors start with **declarative HTML attributes** (what you would expect from a custom element). The same options are also available as **element properties** in JavaScript; setting either updates the other where reflection applies.

### Declarative markup

```html
<script type="module">
  import { registerFormieWebComponents } from '@verbb/formie-web-components';
  import '@verbb/formie-browser/css/formie.css';

  registerFormieWebComponents();
</script>

<formie-form
  transport="rest"
  form-handle="contactForm"
  endpoint="https://formie.test"
  theme="formie"
></formie-form>
```

`mode` defaults to `html` if omitted, but spelling it out matches how you think about the element in docs and examples.

### JavaScript properties

Use this when the element is created or configured from a script (or when you need options that are not representable as attributes, such as `themeConfig` or a preloaded `payload`):

```js
import { registerFormieWebComponents } from '@verbb/formie-web-components';

registerFormieWebComponents();

const form = document.createElement('formie-form');
form.mode = 'server-rendered';
form.transport = 'rest';
form.endpoint = 'https://craft.ddev.site:8443';
form.formHandle = 'singlePage';
form.theme = 'formie';

root.append(form);
```

This is the same configuration as the markup example above, not a separate API.

### Attributes

`<formie-form>` observes these attributes (HTML uses **kebab-case**; in JavaScript, use the **camelCase** property names shown in the property example):

| Name | Type | Required | Description |
| --- | --- | --- | --- |
| `form-handle` | `string` | Yes | Handle of the form to load. |
| `transport` | `string` | No | `rest` or `graphql`. If omitted, set `transport` from JavaScript or rely on defaults only where documented for your setup. |
| `endpoint` | `string` | No | For REST, pass the Craft base URL or a full render URL. For GraphQL, pass your GraphQL endpoint (often `/api` or an absolute URL). If omitted on same-origin REST installs, the element can fall back to the default render action path. |
| `base-url` | `string` | No | Prefixes a relative `endpoint` when you need to resolve against a fixed origin. |
| `theme` | `string` | No | `formie` or `none`. |
| `site-id` | `number` | No | Requests the form for a specific site. |
| `locale` | `string` | No | Advanced override for locale-sensitive rendering and formatting. Most setups can rely on `site-id`. |
| `auto-visible` | `boolean` | No | Reveals the mounted root automatically when it becomes ready. |
| `mode` | `string` | No | Use `html` for this path (default). |
| `static-cache` | `boolean` | No | Advanced token-refresh behavior for static or cached HTML reuse. |
| `refresh-tokens` | `boolean` | No | Refreshes CSRF, request, render, and captcha tokens as Formie needs them. |

Options that are **objects** (`themeConfig`, preloaded `payload`) are only available as **JavaScript properties** on the element, not as attributes.

### Element events

The custom element dispatches these wrapper-level events:

| Name | Description |
| --- | --- |
| `formie-mounted` | Fired after the form mounts. `event.detail.id` contains the mounted instance id. |
| `formie-unmounted` | Fired after the form unmounts. |

```html
<script type="module">
  const form = document.querySelector('formie-form');

  form?.addEventListener('formie-mounted', (event) => {
    console.log('Mounted:', event.detail.id);
  });
</script>
```

The rendered form still emits the normal browser `formie:*` DOM events inside the custom element host.

## Client escape hatch

Use `createFormieClient()` when the custom-element wrapper is not enough:

```html
<script type="module">
  import { createFormieClient } from '@verbb/formie-web-components';
  import '@verbb/formie-browser/css/formie.css';

  const client = createFormieClient();
  const root = document.querySelector('#contact-form');

  if (root) {
    await client.mount(root, {
      mode: 'server-rendered',
      transport: 'rest',
      endpoint: 'https://formie.test',
      formHandle: 'contactForm',
      theme: 'formie',
    });
  }
</script>

<div id="contact-form"></div>
```

This returns the same browser client surface as `@verbb/formie-browser`, including `mount()`, `unmount()`, `update()`, `scan()`, `observe()`, and module registration.

Use this path when you want full control without the custom-element lifecycle, or when you are porting code that already uses `createFormieClient()` from `@verbb/formie-browser`.

For GraphQL server-rendered forms, you can set `transport="graphql"` and `endpoint` on `<formie-form>`; for preloaded HTML payloads or `themeConfig`, set the `payload` / `themeConfig` **properties** on the element, or use the client directly.
