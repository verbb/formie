# Vue

Use `@verbb/formie-vue` when Vue owns the form surface.

If you want to see the full Vue integration in action, use the [Vue starter](https://formie-starters.verbb.io/vue) as a complete example app.

Start with the component path first:

```vue
<script setup lang="ts">
import { FormieForm } from '@verbb/formie-vue';
import '@verbb/formie-browser/css/formie.css';

function onSuccess(result: unknown) {
  console.log('Submit ok:', result);
}

function onError(result: unknown) {
  console.log('Submit failed:', result);
}
</script>

<template>
  <FormieForm
    transport="rest"
    endpoint="https://formie.test"
    form-handle="contactForm"
    theme="formie"
    :on-success="onSuccess"
    :on-error="onError"
  />
</template>
```

The package has two rendering paths:

- server-rendered forms, where Formie still owns the rendered HTML and browser behavior
- client-rendered forms, where Vue renders from Formie's client definition

If Craft is already rendering the final form HTML directly into the page and Vue is only wrapping the surrounding screen, use the [Browser](/browser/) docs instead.

## Server-rendered Forms

Use server-rendered forms when:

- you want the fastest, lowest-effort path to a working Vue form
- you want Formie to keep owning the rendered markup, validation, and submit behavior
- you want to keep the browser package theme, events, and modules

Server-rendered forms come in two shapes:

- `<FormieForm transport="..." />` for the simplest path
- `<FormieForm :source="{ payload }" />` when your app already fetched the HTML payload

Use `useFormieHtml()` only when you need lower-level ref ownership or imperative submit access.

Start with [Server-rendered](/vue/server-rendered/overview).

## Client-rendered Forms

Use client-rendered forms when:

- Vue should render the form UI instead of mounting Formie-owned HTML
- you are happy to take on more implementation work in exchange for full UI control
- you need `components`, `fieldComponents`, or `slots` on `<FormieClientForm />`
- you want form composables such as `useFormie()` and `useFormieField()`

In this setup, Vue renders from Formie's client definition and Formie owns state and submission behind it.

Start with [Client-rendered mode](/vue/client-rendered/overview).

## Transport

Once you choose a rendering path, you also need to choose how the form data is loaded and submitted.

Use REST when:

- you want the simplest transport story
- you want the closest fit to the client-rendered controllers
- you are wiring the app against Formie's standard frontend actions

Use GraphQL when:

- your app already standardizes on GraphQL
- you want transport to stay inside an existing GraphQL client workflow
- you are preloading `formieHtmlForm` for server-rendered forms or `formieClientForm` for client-rendered forms
- For server-rendered forms, GraphQL usually means `transport="graphql"` or preloading `formieHtmlForm`, but submission still happens through the rendered form POST.
- For client-rendered forms, GraphQL means loading `formieClientForm` and using GraphQL mutations for submit, session refresh, and page changes.
