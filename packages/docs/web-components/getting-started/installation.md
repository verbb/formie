# Installation

Install `@verbb/formie-web-components` into your app:

```bash
npm install @verbb/formie-web-components
```

`@verbb/formie-browser` and `@verbb/formie-core` are dependencies of this package; you normally do not add them unless you call their APIs directly.

## Register the elements

Import the package and register the custom elements once when your app boots:

```ts
import { registerFormieWebComponents } from '@verbb/formie-web-components';

registerFormieWebComponents();
```

## CSS

### Server-rendered forms (`formie-form`)

If you use the shipped Formie browser UI, import CSS from `@verbb/formie-browser`:

```ts
import '@verbb/formie-browser/css/formie.css';
```

You can split base and theme layers if needed:

```ts
import '@verbb/formie-browser/css/formie-base.css';
import '@verbb/formie-browser/css/formie-theme.css';
```

### Client-rendered forms (`formie-core-form`)

Client-rendered forms render their own structure and apply stable CSS classes (for example `formie-page-actions` for page actions). You do **not** need the browser theme CSS unless you intentionally want to reuse those variables or utilities. Style the host and inner markup with your own CSS.

## Browser requirements

- Register the custom elements **before** inserting `<formie-form>` or `<formie-core-form>` into the document.
- **`formie-form`:** needs `form-handle` (attribute `form-handle` or property `formHandle`). Set `transport` and `endpoint` for your setup; see [Server-rendered](/web-components/server-rendered/overview).
- **`formie-core-form`:** needs `form-handle` and `endpoint` (REST: Craft base URL; GraphQL: API URL, often `/api`). See [Client-rendered forms](/web-components/client-rendered/overview).
- If the frontend and Craft live on different domains, point `endpoint` at Craft (or use `base-url` on `formie-form` with a relative `endpoint` where supported).
- If the Craft control panel lives under something like `/admin`, do not use the CP URL. Use the public site root or the correct GraphQL endpoint.

## Next step

Choose the right rendering path on [Web Components](/web-components/).
