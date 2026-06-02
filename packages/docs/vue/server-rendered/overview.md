# Overview

Choose server-rendered forms when you want the simplest Vue integration and you are happy for Formie to keep owning the form UI.

If you want to see server-rendered forms in a fuller app setup, use the [Vue starter](https://formie-starters.verbb.io/vue) as a working example.

In this setup:

- Vue owns the host component
- Formie still owns the rendered HTML inside it
- the browser package still owns validation, submit flow, and browser-side behavior

Browser events and browser modules still apply for server-rendered forms. Use the [Browser](/browser/) docs when you need deeper browser behavior extension points.

## Component

This is the simplest HTML-mode entry point.

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

In templates, use **kebab-case** for multi-word props (`form-handle`, `theme-config`). The underlying options match the React adapter.

### Props

`<FormieForm />` supports these HTML-mode props:

| Name | Type | Required | Description |
| --- | --- | --- | --- |
| `transport` | `'rest' \| 'graphql'` | Yes, unless `:source="{ payload }"` | Chooses how the HTML payload is loaded. |
| `endpoint` | `string` | Yes, unless `:source="{ payload }"` | For REST, pass the Craft base URL and Formie resolves the browser action URLs. For GraphQL, pass the GraphQL endpoint directly, usually `/api`. |
| `formHandle` | `string` | Yes, unless `:source="{ payload }"` | Handle of the form to load. |
| `refreshTokens` | `boolean` | No | Refreshes CSRF, request, render, and captcha tokens as Formie needs them. |
| `locale` | `string` | No | Advanced override for locale-sensitive rendering and formatting. Most setups can rely on `siteId`. |
| `siteId` | `number` | No | Requests the form for a specific site. |
| `autoVisible` | `boolean` | No | Reveals the mounted root automatically when it becomes ready. |
| `theme` | `'formie' \| 'none'` | No | Uses the shipped browser theme or skips it. |
| `themeConfig` | `Record<string, unknown>` | No | Passes additional theme configuration to the browser theme layer. |
| `source` | `{ payload: FormEndpointPayload }` | No | Uses a preloaded HTML payload instead of loading one during mount. |
| `className` | `string` | No | Adds a class to the host element that Vue renders. |

With `transport="graphql"`, GraphQL only loads the HTML payload. Submit still uses the rendered form action rather than a GraphQL mutation.

### Callback props and events

For the common path, start with `onReady`, `onSuccess`, and `onError`.

`<FormieForm />` supports the same callbacks as props (`onReady`, `onSuccess`, …) **and** emits matching events (`ready`, `success`, …). Use either style.

| Name (prop) | Emit name | Description |
| --- | --- | --- |
| `onMount` | `mount` | Called after the HTML form instance mounts. Receives a `FormieFormInstance`. |
| `onReady` | `ready` | Called when the mounted instance is ready for use. Receives a `FormieFormInstance`. |
| `onUnmount` | `unmount` | Called after the form is unmounted. |
| `onResult` | `result` | Called for every submit result. Receives a `FormSubmitResult`. |
| `onSuccess` | `success` | Called when submit succeeds. Receives a `FormSubmitResult`. |
| `onError` | `error` | Called when submit fails. Receives a `FormSubmitResult`. |
| `onSubmitResult` | `submit-result` | Called for every submit result. Receives a `FormSubmitResult`. |
| `onSubmitSuccess` | `submit-success` | Called when submit succeeds. Receives a `FormSubmitResult`. |
| `onSubmitError` | `submit-error` | Called when submit fails. Receives a `FormSubmitResult`. |
| `onEvent` | `event` | Called for browser events exposed through the Vue wrapper. Receives a `FormieVueEvent`. |

The `onSubmit*` props and `submit-*` events remain available as the more explicit lower-level aliases.

```vue
<script setup lang="ts">
import { FormieForm } from '@verbb/formie-vue';

function onReady(instance: unknown) {
  console.log('Mounted:', (instance as { id: string }).id);
}

function onSuccess(result: unknown) {
  console.log('Submit ok:', result);
}

function onEvent(event: { name: string; payload: unknown }) {
  console.log(event.name, event.payload);
}
</script>

<template>
  <FormieForm
    transport="rest"
    endpoint="https://formie.test"
    form-handle="contactForm"
    theme="formie"
    :on-ready="onReady"
    :on-success="onSuccess"
    :on-event="onEvent"
  />
</template>
```

Template-only equivalent with `v-on`:

```vue
<template>
  <FormieForm
    transport="rest"
    endpoint="https://formie.test"
    form-handle="contactForm"
    theme="formie"
    @ready="(instance) => console.log('Mounted:', instance.id)"
    @success="(result) => console.log('Submit ok:', result)"
    @event="(event) => console.log(event.name, event.payload)"
  />
</template>
```

## Advanced composable

`useFormieHtml()` mounts the same server-rendered browser behavior, but lets your component own the host ref and imperative API directly.

```vue
<script setup lang="ts">
import { useFormieHtml } from '@verbb/formie-vue';
import '@verbb/formie-browser/css/formie.css';

const { rootRef } = useFormieHtml({
  transport: 'rest',
  endpoint: 'https://formie.test',
  formHandle: 'contactForm',
  theme: 'formie',
});
</script>

<template>
  <div ref="rootRef" />
</template>
```

This is still the server-rendered path, but it is the lower-level escape hatch rather than the recommended starting point.

### Options

`useFormieHtml()` accepts the same HTML mount options as the browser client, except `mode` is fixed to `'server-rendered'`:

| Name | Type | Required | Description |
| --- | --- | --- | --- |
| `transport` | `'rest' \| 'graphql'` | Yes, unless `payload` | Chooses how the HTML payload is loaded. |
| `endpoint` | `string` | Yes, unless `payload` | For REST, pass the Craft base URL and Formie resolves the browser action URLs. For GraphQL, pass the GraphQL endpoint directly, usually `/api`. |
| `formHandle` | `string` | Yes, unless `payload` | Handle of the form to load. |
| `payload` | `FormEndpointPayload` | No | Uses a preloaded HTML payload instead of loading one during mount. |
| `refreshTokens` | `boolean` | No | Refreshes CSRF, request, render, and captcha tokens as Formie needs them. |
| `locale` | `string` | No | Advanced override for locale-sensitive rendering and formatting. Most setups can rely on `siteId`. |
| `siteId` | `number` | No | Requests the form for a specific site. |
| `autoVisible` | `boolean` | No | Reveals the mounted root automatically when it becomes ready. |
| `theme` | `'formie' \| 'none'` | No | Uses the shipped browser theme or skips it. |
| `themeConfig` | `Record<string, unknown>` | No | Passes additional theme configuration to the browser theme layer. |

### Return value

The composable returns:

| Name | Type | Description |
| --- | --- | --- |
| `rootRef` | `Ref<HTMLElement \| null>` | Host element ref that Formie mounts into. |
| `state.instance` | `ShallowRef<FormieFormInstance \| null>` | Mounted instance, or `null` before mount. |
| `state.isMounted` | `Ref<boolean>` | Whether the form is currently mounted. |
| `state.error` | `Ref<Error \| null>` | Mount error, if mount failed. |
| `submit` | `function` | Imperative submit access for the mounted form. Accepts an optional `FormAction` and resolves to `FormSubmitResult \| null`. |

### Events with the composable

The composable does not expose callback props. Subscribe to browser events from `state.instance` instead:

```vue
<script setup lang="ts">
import { watch } from 'vue';
import { useFormieHtml } from '@verbb/formie-vue';

const form = useFormieHtml({
  transport: 'rest',
  endpoint: 'https://formie.test',
  formHandle: 'contactForm',
  theme: 'formie',
});
const { rootRef } = form;

watch(
  () => form.state.instance.value,
  (instance, _previous, onCleanup) => {
    if (!instance) {
      return;
    }

    const unsubscribe = instance.on('formie:submit:result', (result) => {
      console.log('Submit result:', result);
    });

    onCleanup(unsubscribe);
  },
  { immediate: true },
);
</script>

<template>
  <div ref="rootRef" />
</template>
```

## Advanced client

Use `createVueFormieClient()` when you need lower-level browser-client control instead of the Vue composable wrapper:

```vue
<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { createVueFormieClient } from '@verbb/formie-vue';
import '@verbb/formie-browser/css/formie.css';

const client = createVueFormieClient();
const root = ref<HTMLElement | null>(null);

onMounted(async () => {
  if (!root.value) {
    return;
  }

  await client.mount(root.value, {
    mode: 'server-rendered',
    transport: 'rest',
    endpoint: 'https://formie.test',
    formHandle: 'contactForm',
    theme: 'formie',
  });
});

onBeforeUnmount(async () => {
  if (!root.value) {
    return;
  }

  await client.unmount(root.value);
});
</script>

<template>
  <div ref="root" />
</template>
```

This returns the same browser client surface as `@verbb/formie-browser`, including `mount()`, `unmount()`, `update()`, `scan()`, `observe()`, and module registration.

## Preloaded payloads

If your app already fetched the HTML payload, pass it into Vue instead of fetching it again during mount:

```vue
<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { FormieForm, type FormEndpointPayload } from '@verbb/formie-vue';
import '@verbb/formie-browser/css/formie.css';

async function loadFormPayload(endpoint: string, handle: string): Promise<FormEndpointPayload> {
  const response = await fetch(endpoint, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      handle,
    }),
  });

  if (!response.ok) {
    throw new Error('Failed to load form payload.');
  }

  return response.json();
}

const payload = ref<FormEndpointPayload | null>(null);

onMounted(() => {
  void loadFormPayload('https://formie.test/your-render-endpoint', 'contactForm').then((result) => {
    payload.value = result;
  });
});
</script>

<template>
  <FormieForm v-if="payload" :source="{ payload }" theme="formie" />
</template>
```

For composable usage, pass the same payload as `payload` instead of `:source="{ payload }"`.
