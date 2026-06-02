# @verbb/formie-web-components

Custom elements for Formie’s browser runtime:

- **`formie-form`** — server-rendered forms via `@verbb/formie-browser` (`createFormieClient`, themes, server-rendered markup).
- **`formie-core-form`** — Definition + `@verbb/formie-core` runtime (REST/GraphQL envelope load, Lit field tree, optional registry overrides).
- **`formie-internal-signature`** — Internal element used by the core form renderer for draw-signature fields (registered with the public elements).

## Install

```bash
npm install @verbb/formie-browser @verbb/formie-web-components
```

(`@verbb/formie-core` is a dependency of this package.)

## Usage

```ts
import { registerFormieWebComponents } from '@verbb/formie-web-components';
import '@verbb/formie-browser/css/formie.css';

registerFormieWebComponents();
```

Server-rendered forms:

```html
<formie-form
  form-handle="contactForm"
  endpoint="https://your-craft.test/actions/formie/server/forms/render"
></formie-form>
```

Client-rendered runtime UI:

```html
<formie-core-form
  form-handle="contactForm"
  endpoint="https://your-craft.test"
  transport="rest"
></formie-core-form>
```

## Public API

Import from `@verbb/formie-web-components` only. CSS continues to come from `@verbb/formie-browser`.

Advanced: `FormieRegistry`, `renderFormView`, `FORMIE_CONTROL_VALUE_EVENT`, etc., for custom field controls, optional per-field hosts, and layout regions.
