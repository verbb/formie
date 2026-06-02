# Overview

Choose client-rendered forms when Vue should render the visible form UI and you want full control over components, layout, and composables.

If you want to see client-rendered forms in a fuller app setup, use the [Vue starter](https://formie-starters.verbb.io/vue) as a working example.

In this setup:

- Vue renders from Formie's client definition
- Formie owns state, page progression, validation, and submission behind it
- your app owns the visible UI through Vue components, field components, slots, and composables

## Component

Start with `<FormieClientForm />`:

```vue
<script setup lang="ts">
import { FormieClientForm } from '@verbb/formie-vue';
</script>

<template>
  <FormieClientForm
    
    transport="rest"
    endpoint="https://formie.test"
    form-handle="contactForm"
  />
</template>
```

This is the simplest client-rendered entry point.

### Props

`<FormieClientForm />` supports these client-rendered props:

| Name | Type | Required | Description |
| --- | --- | --- | --- |
| `transport` | `'rest' \| 'graphql'` | Yes, unless `source` | Chooses how the client definition envelope is loaded. |
| `endpoint` | `string` | Yes, unless `source` | For REST, pass the Craft base URL and Formie resolves the browser action URLs. For GraphQL, pass the GraphQL endpoint directly, usually `/api`. |
| `formHandle` | `string` | Yes, unless `source` | Handle of the form to load. |
| `siteId` | `number` | No | Requests the form for a specific site. |
| `source` | `FormieDefinitionSource` | No | Uses a preloaded client definition envelope and transport metadata. |
| `components` | `FormieVueComponents` | No | Replaces top-level `Form`, `Page`, **`Field`**, or error-summary components. |
| `fieldComponents` | `map` | No | Replaces specific field-type renderers. Keys are `FrontendFieldType` values and values are Vue components. |
| `slots` | `map` | No | Intercepts smaller layout regions inside the default component tree. Keys are slot names and values are Vue components. |
| `className` | `string` | No | Adds a class to the rendered form root. |

### Callback props and events

For the common path, start with `onReady`, `onSuccess`, and `onError`.

`<FormieClientForm />` supports the same callbacks as props **and** emits matching events. Use either style.

| Name (prop) | Emit name | Description |
| --- | --- | --- |
| `onMount` | `mount` | Called after the form instance mounts. Receives a `FrontendFormInstance`. |
| `onReady` | `ready` | Called when the form instance is ready for use. Receives a `FrontendFormInstance`. |
| `onUnmount` | `unmount` | Called after the form is unmounted. |
| `onResult` | `result` | Called for every submit result. Receives a `FrontendSubmitResult`. |
| `onSuccess` | `success` | Called when submit succeeds. Receives a `FrontendSubmitResult`. |
| `onError` | `error` | Called when submit fails. Receives a `FrontendSubmitResult`. |
| `onSubmitResult` | `submit-result` | Called for every submit result. Receives a `FrontendSubmitResult`. |
| `onSubmitSuccess` | `submit-success` | Called when submit succeeds. Receives a `FrontendSubmitResult`. |
| `onSubmitError` | `submit-error` | Called when submit fails. Receives a `FrontendSubmitResult`. |
| `onEvent` | `event` | Called for client events exposed through the Vue wrapper. Receives a `FormieVueEvent`. |

The `onSubmit*` props and `submit-*` events remain available as the more explicit lower-level aliases.

```vue
<script setup lang="ts">
import { FormieClientForm } from '@verbb/formie-vue';

function onReady(formie: unknown) {
  console.log('Form ready:', formie);
}

function onSuccess(result: unknown) {
  console.log('Submit ok:', result);
}

function onEvent(event: { name: string; payload: unknown }) {
  console.log(event.name, event.payload);
}
</script>

<template>
  <FormieClientForm
    
    transport="rest"
    endpoint="https://formie.test"
    form-handle="contactForm"
    :on-ready="onReady"
    :on-success="onSuccess"
    :on-event="onEvent"
  />
</template>
```

## Transport

Once you choose client-rendered forms, you also need to choose how the client definition envelope is loaded and how submissions are sent.

Use REST when:

- you want the simplest transport story
- you want the closest fit to the client-rendered controllers
- you are wiring the app against Formie's standard frontend actions

Use GraphQL when:

- your app already standardizes on GraphQL
- you want transport to stay inside an existing GraphQL client workflow
- you want to load `formieClientForm` yourself or through your existing data layer

For client-rendered forms, GraphQL covers more than the initial load. Formie also uses GraphQL mutations for submit, session refresh, and page changes.

## GraphQL query

For GraphQL client-rendered forms, load `formieClientForm`:

```graphql
query ClientForm($handle: String!, $siteId: Int) {
  formieClientForm(handle: $handle, siteId: $siteId) {
    schemaVersion
    definition
    session {
      id
      currentPageId
      tokens
      continuation
    }
  }
}
```

That query returns the `FrontendFormEnvelope` Vue needs: `schemaVersion`, `definition`, and `session`.

## Manual GraphQL mutations

If you use `<FormieClientForm transport="graphql" />`, Formie handles submit, session refresh, and page changes for you.

If your app owns the GraphQL client directly, use these mutations:

- `submitFormieClientForm`
- `refreshFormieClientSession`
- `setFormieClientPage`

For example, submit uses `submitFormieClientForm`:

```graphql
mutation SubmitForm($input: FormieClientSubmitInput!) {
  submitFormieClientForm(input: $input) {
    success
    submissionUid
    currentPageId
    nextPageId
    previousPageId
    isFinalPage
    errors
    messages
    session {
      id
      currentPageId
      tokens
      continuation
    }
  }
}
```

## Preloaded definition

If your app already fetched the client definition envelope, pass it into `source` instead of loading it again inside the form component:

```vue
<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { FormieClientForm, type FrontendFormEnvelope } from '@verbb/formie-vue';

const query = `
query ClientForm($handle: String!, $siteId: Int) {
  formieClientForm(handle: $handle, siteId: $siteId) {
    schemaVersion
    definition
    session {
      id
      currentPageId
      tokens
      continuation
    }
  }
}`;

async function loadClientEnvelope(endpoint: string, handle: string): Promise<FrontendFormEnvelope> {
  const response = await fetch(endpoint, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      query,
      variables: {
        handle,
      },
    }),
  });

  if (!response.ok) {
    throw new Error('Failed to load client definition envelope.');
  }

  const body = await response.json();

  return body.data.formieClientForm;
}

const envelope = ref<FrontendFormEnvelope | null>(null);

onMounted(() => {
  void loadClientEnvelope('https://formie.test/api', 'contactForm').then((result) => {
    envelope.value = result;
  });
});
</script>

<template>
  <FormieClientForm
    v-if="envelope"
    
    :source="{
      definition: envelope,
      transport: {
        type: 'graphql',
        endpoint: 'https://formie.test/api',
        formHandle: 'contactForm',
      },
    }"
  />
</template>
```
