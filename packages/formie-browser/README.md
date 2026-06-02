# @verbb/formie-browser

DOM-focused Formie runtime for mounting and managing forms outside the Craft plugin repo.

## Install

```bash
npm install @verbb/formie-browser
```

## Usage

```ts
import { createFormieClient } from '@verbb/formie-browser';
import '@verbb/formie-browser/css/formie.css';

const client = createFormieClient();

await client.mount(document.querySelector('#formie-root'), {
    endpoint: '/actions/formie/server/forms/render',
    formHandle: 'contactForm',
});
```

## Public Surface

Use only the root package entry and the exported CSS files:

- `@verbb/formie-browser`
- `@verbb/formie-browser/css/formie.css`
- `@verbb/formie-browser/css/formie-base.css`
- `@verbb/formie-browser/css/formie-theme.css`

Anything else inside the package is private implementation detail and may change without notice.
