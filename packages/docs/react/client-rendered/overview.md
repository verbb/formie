# Overview

Choose client-rendered forms when React should render the visible form UI and you want full control over components, layout, and hooks.

If you want to see client-rendered forms in a fuller app setup, use the [React starter](https://formie-starters.verbb.io/react) as a working example.

In this setup:

- React renders from Formie's client definition
- Formie owns state, page progression, validation, and submission behind it
- your app owns the visible UI through React components, field components, slots, and hooks

## Component

Start with `<FormieClientForm />`:

```tsx
import { FormieClientForm } from '@verbb/formie-react';

export function ContactForm() {
  return (
    <FormieClientForm
      
      transport="rest"
      endpoint="https://formie.test"
      formHandle="contactForm"
    />
  );
}
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
| `components` | `FormieReactComponents` | No | Replaces top-level `Form`, `Page`, **`Field`**, or error-summary components. |
| `fieldComponents` | `map` | No | Replaces specific field-type renderers. Keys are `FrontendFieldType` values and values are React components. |
| `slots` | `map` | No | Intercepts smaller layout regions inside the default component tree. Keys are slot names and values are React components. |
| `className` | `string` | No | Adds a class to the rendered form root. |

### Callback props

For the common path, start with `onReady`, `onSuccess`, and `onError`.

`<FormieClientForm />` also supports these callback props:

| Name | Type | Description |
| --- | --- | --- |
| `onMount` | `function` | Called after the form instance mounts. Receives a `FrontendFormInstance`. |
| `onReady` | `function` | Called when the form instance is ready for use. Receives a `FrontendFormInstance`. |
| `onUnmount` | `function` | Called after the form is unmounted. |
| `onResult` | `function` | Called for every submit result. Receives a `FrontendSubmitResult`. |
| `onSuccess` | `function` | Called when submit succeeds. Receives a `FrontendSubmitResult`. |
| `onError` | `function` | Called when submit fails. Receives a `FrontendSubmitResult`. |
| `onSubmitResult` | `function` | Called for every submit result. Receives a `FrontendSubmitResult`. |
| `onSubmitSuccess` | `function` | Called when submit succeeds. Receives a `FrontendSubmitResult`. |
| `onSubmitError` | `function` | Called when submit fails. Receives a `FrontendSubmitResult`. |
| `onEvent` | `function` | Called for client events exposed through the React wrapper. Receives a `FormieReactEvent`. |

The `onSubmit*` names remain available as the more explicit lower-level aliases.

```tsx
import { FormieClientForm } from '@verbb/formie-react';

export function ContactForm() {
  return (
    <FormieClientForm
      
      transport="rest"
      endpoint="https://formie.test"
      formHandle="contactForm"
      onReady={(formie) => {
        console.log('Form ready:', formie);
      }}
      onSuccess={(result) => {
        console.log('Submit ok:', result);
      }}
      onEvent={(event) => {
        console.log(event.name, event.payload);
      }}
    />
  );
}
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

That query returns the `FrontendFormEnvelope` React needs: `schemaVersion`, `definition`, and `session`.

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

```tsx
import { useEffect, useState } from 'react';
import { FormieClientForm, type FrontendFormEnvelope } from '@verbb/formie-react';

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

export function ContactScreen() {
  const [envelope, setEnvelope] = useState<FrontendFormEnvelope | null>(null);

  useEffect(() => {
    void loadClientEnvelope('https://formie.test/api', 'contactForm').then(setEnvelope);
  }, []);

  if (!envelope) {
    return null;
  }

  return (
    <FormieClientForm
      
      source={{
        definition: envelope,
        transport: {
          type: 'graphql',
          endpoint: 'https://formie.test/api',
          formHandle: 'contactForm',
        },
      }}
    />
  );
}
```
