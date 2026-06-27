# Vue and Web Components starter walkthrough

Formie ships first-class packages for Vue and Web Components alongside React. This walkthrough covers the same four integration stories — server-rendered HTML vs client-rendered UI, REST vs GraphQL — using the official starters as the reference implementation.

## Prerequisites

- Formie on Craft with at least one published form
- Node.js and npm (or pnpm/yarn)
- [Frontend Assets](/frontend/frontend-assets)

## Package overview

| Package | npm | Use when |
| --- | --- | --- |
| `@verbb/formie-vue` | Vue 3 bindings | Your app is Vue or Nuxt |
| `@verbb/formie-web-components` | Custom elements | You want framework-agnostic embeds, or Vue/React coexist on one page |
| `@verbb/formie-browser` | Shared browser package | Required peer for both packages |
| `@verbb/formie-core` | Form definition + state | Required for client-rendered mode |

Install pattern (Vue example):

```bash
npm install @verbb/formie-browser @verbb/formie-core @verbb/formie-vue vue
```

Import CSS once:

```js
import '@verbb/formie-browser/css/formie.css';
```

## Vue — server-rendered form

The `<FormieForm />` component mirrors the React API. Formie loads the payload, mounts HTML, and owns browser behaviour.

```vue
<script setup>
import { FormieForm } from '@verbb/formie-vue';
import '@verbb/formie-browser/css/formie.css';
</script>

<template>
    <FormieForm
        transport="rest"
        endpoint="https://your-craft-site.test"
        form-handle="contactForm"
        theme="formie"
        @submit-success="(result) => console.log(result)"
    />
</template>
```

Props use kebab-case in templates (`form-handle`, `site-id`, `static-cache`). For GraphQL, set `transport="graphql"` and point `endpoint` at your GraphQL URL.

### Pre-fetched payload

When your app already has the bootstrap payload:

```vue
<FormieForm :source="{ payload }" />
```

## Vue — client-rendered form

Use `<FormieClientForm />` when you want Vue components for each field:

```vue
<script setup>
import { FormieClientForm } from '@verbb/formie-vue';
</script>

<template>
    <FormieClientForm
        transport="graphql"
        endpoint="https://your-craft-site.test/api"
        form-handle="contactForm"
        :components="{
            Field({ field, children, errors }) {
                return h('div', { class: 'field' }, [
                    h('label', field.label),
                    children,
                    ...errors.map((e) => h('p', { class: 'error' }, e)),
                ]);
            },
        }"
    />
</template>
```

Composable helpers (`useFormie`, `useFormieField`, `useFormiePage`) match the React hooks for custom layouts.

## Nuxt

Start from the [Nuxt starter](https://formie-starters.verbb.io/nuxt). It demonstrates:

- Server-side payload fetching in a route loader
- Client-only mounting for browser-only APIs (captchas, file uploads)
- REST and GraphQL transport switching via environment variables

For Nuxt 3+, wrap `<FormieForm />` in `<ClientOnly>` when the page is statically generated and token refresh must run in the browser.

## Web Components — drop-in embed

Web Components work anywhere you can add a script tag — static HTML, WordPress, legacy PHP, or inside Vue/React without a framework binding layer.

```html
<link rel="stylesheet" href="/path/to/formie.css">

<formie-form
    transport="rest"
    endpoint="https://your-craft-site.test"
    form-handle="contactForm"
></formie-form>

<script type="module">
    import '@verbb/formie-web-components';
</script>
```

Register the custom element once; then use `<formie-form>` or `<formie-client-form>` in markup.

### Client-rendered custom element

For app-owned rendering via slots:

```html
<formie-client-form
    transport="graphql"
    endpoint="https://your-craft-site.test/api"
    form-handle="contactForm"
></formie-client-form>
```

Listen for events on the element:

```js
const form = document.querySelector('formie-form');

form?.addEventListener('formie:submit:result', (event) => {
    console.log(event.detail);
});
```

Event names match the browser package DOM events (`formie:submit:result`, `formie:page:navigate:after`, and so on).

## Choosing REST vs GraphQL

| Transport | Pros | Considerations |
| --- | --- | --- |
| **REST** | Simple endpoint, easy local dev | Requires Formie REST routes enabled |
| **GraphQL** | Typed schema, single API surface | Needs public schema scopes configured |

Both transports use the same session token model. Bootstrap via `formieClientForm`, submit via `submitFormieClientForm`. See [GraphQL submission flow end-to-end](/guides/frontend-headless/graphql-submission-flow-end-to-end).

## Static caching

Pass static-cache attributes on the custom element or props on Vue components:

```vue
<FormieForm
    transport="rest"
    endpoint="https://your-craft-site.test"
    form-handle="contactForm"
    :static-cache="true"
    :refresh-tokens="true"
/>
```

```html
<formie-form
    transport="rest"
    endpoint="https://your-craft-site.test"
    form-handle="contactForm"
    static-cache
    refresh-tokens
></formie-form>
```

See [Cached forms in production](/guides/frontend-headless/cached-forms-in-production).

## Starter repos

Clone and run the official starters — they are the canonical review surface for each package:

- [Vue starter](https://formie-starters.verbb.io/vue)
- [Web Components starter](https://formie-starters.verbb.io/web-components)
- [Nuxt starter](https://formie-starters.verbb.io/nuxt)

Each starter includes working examples for HTML mode, component mode, REST, and GraphQL. Use them as copy-paste references before building from the package README alone.

## Related

- [Frontend Assets](/frontend/frontend-assets)
- [Building a headless contact form with Formie React](/guides/frontend-headless/building-a-headless-contact-form-with-formie-react) — parallel walkthrough for React
- [GraphQL submission flow end-to-end](/guides/frontend-headless/graphql-submission-flow-end-to-end)
- [Vue package docs](https://docs.verbb.io/formie/vue/)
- [Web Components package docs](https://docs.verbb.io/formie/web-components/)
