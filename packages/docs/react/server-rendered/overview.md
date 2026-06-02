# Overview

Choose server-rendered forms when you want the simplest React integration and you are happy for Formie to keep owning the form UI.

If you want to see server-rendered forms in a fuller app setup, use the [React starter](https://formie-starters.verbb.io/react) as a working example.

In this setup:

- React owns the host component
- Formie still owns the rendered HTML inside it
- the browser package still owns validation, submit flow, and browser-side behavior

Browser events and browser modules still apply for server-rendered forms. Use the [Browser](/browser/) docs when you need deeper browser behavior extension points.

## Component

This is the simplest HTML-mode entry point.

```tsx
import { FormieForm } from '@verbb/formie-react';
import '@verbb/formie-browser/css/formie.css';

export function ContactForm() {
  return (
    <FormieForm
      transport="rest"
      endpoint="https://formie.test"
      formHandle="contactForm"
      theme="formie"
      onSuccess={(result) => {
        console.log('Submit ok:', result);
      }}
      onError={(result) => {
        console.log('Submit failed:', result);
      }}
    />
  );
}
```

### Props

`<FormieForm />` supports these HTML-mode props:

| Name | Type | Required | Description |
| --- | --- | --- | --- |
| `transport` | `'rest' \| 'graphql'` | Yes, unless `source={{ payload }}` | Chooses how the HTML payload is loaded. |
| `endpoint` | `string` | Yes, unless `source={{ payload }}` | For REST, pass the Craft base URL and Formie resolves the browser action URLs. For GraphQL, pass the GraphQL endpoint directly, usually `/api`. |
| `formHandle` | `string` | Yes, unless `source={{ payload }}` | Handle of the form to load. |
| `refreshTokens` | `boolean` | No | Refreshes CSRF, request, render, and captcha tokens as Formie needs them. |
| `locale` | `string` | No | Advanced override for locale-sensitive rendering and formatting. Most setups can rely on `siteId`. |
| `siteId` | `number` | No | Requests the form for a specific site. |
| `autoVisible` | `boolean` | No | Reveals the mounted root automatically when it becomes ready. |
| `theme` | `'formie' \| 'none'` | No | Uses the shipped browser theme or skips it. |
| `themeConfig` | `Record<string, unknown>` | No | Passes additional theme configuration to the browser theme layer. |
| `source` | `{ payload: FormEndpointPayload }` | No | Uses a preloaded HTML payload instead of loading one during mount. |
| `className` | `string` | No | Adds a class to the host element that React renders. |

With `transport="graphql"`, GraphQL only loads the HTML payload. Submit still uses the rendered form action rather than a GraphQL mutation.

### Callback props

For the common path, start with `onReady`, `onSuccess`, and `onError`.

`<FormieForm />` supports these callback props:

| Name | Type | Description |
| --- | --- | --- |
| `onMount` | `function` | Called after the HTML form instance mounts. Receives a `FormieFormInstance`. |
| `onReady` | `function` | Called when the mounted instance is ready for use. Receives a `FormieFormInstance`. |
| `onUnmount` | `function` | Called after the form is unmounted. |
| `onResult` | `function` | Called for every submit result. Receives a `FormSubmitResult`. |
| `onSuccess` | `function` | Called when submit succeeds. Receives a `FormSubmitResult`. |
| `onError` | `function` | Called when submit fails. Receives a `FormSubmitResult`. |
| `onSubmitResult` | `function` | Called for every submit result. Receives a `FormSubmitResult`. |
| `onSubmitSuccess` | `function` | Called when submit succeeds. Receives a `FormSubmitResult`. |
| `onSubmitError` | `function` | Called when submit fails. Receives a `FormSubmitResult`. |
| `onEvent` | `function` | Called for browser events exposed through the React wrapper. Receives a `FormieReactEvent`. |

The `onSubmit*` names remain available as the more explicit lower-level aliases.

```tsx
import { FormieForm } from '@verbb/formie-react';

export function ContactForm() {
  return (
    <FormieForm
      transport="rest"
      endpoint="https://formie.test"
      formHandle="contactForm"
      theme="formie"
      onReady={(instance) => {
        console.log('Mounted:', instance.id);
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

## Advanced hook

`useFormieHtml()` mounts the same server-rendered browser behavior, but lets your component own the host ref and imperative API directly.

```tsx
import { useFormieHtml } from '@verbb/formie-react';
import '@verbb/formie-browser/css/formie.css';

export function ContactForm() {
  const form = useFormieHtml({
    transport: 'rest',
    endpoint: 'https://formie.test',
    formHandle: 'contactForm',
    theme: 'formie',
  });

  return <div ref={form.rootRef} />;
}
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

The hook returns:

| Name | Type | Description |
| --- | --- | --- |
| `rootRef` | `RefObject<HTMLDivElement \| null>` | Host element ref that Formie mounts into. |
| `state.instance` | `FormieFormInstance \| null` | Mounted instance, or `null` before mount. |
| `state.isMounted` | `boolean` | Whether the form is currently mounted. |
| `state.error` | `Error \| null` | Mount error, if mount failed. |
| `submit` | `function` | Imperative submit access for the mounted form. Accepts an optional `FormAction` and resolves to `FormSubmitResult \| null`. |

### Events with the hook

The hook does not expose callback props. Subscribe to browser events from `state.instance` instead:

```tsx
import { useEffect } from 'react';
import { useFormieHtml } from '@verbb/formie-react';

export function ContactForm() {
  const form = useFormieHtml({
    transport: 'rest',
    endpoint: 'https://formie.test',
    formHandle: 'contactForm',
    theme: 'formie',
  });

  useEffect(() => {
    const instance = form.state.instance;

    if (!instance) {
      return;
    }

    return instance.on('formie:submit:result', (result) => {
      console.log('Submit result:', result);
    });
  }, [form.state.instance]);

  return <div ref={form.rootRef} />;
}
```

## Preloaded payloads

If your app already fetched the HTML payload, pass it into React instead of fetching it again during mount:

```tsx
import { useEffect, useState } from 'react';
import { FormieForm, type FormEndpointPayload } from '@verbb/formie-react';

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

export function ContactScreen() {
  const [payload, setPayload] = useState<FormEndpointPayload | null>(null);

  useEffect(() => {
    void loadFormPayload('https://formie.test/your-render-endpoint', 'contactForm').then(setPayload);
  }, []);

  if (!payload) {
    return null;
  }

  return <FormieForm source={{ payload }} theme="formie" />;
}
```

For hook usage, pass the same payload as `payload` instead of `source={{ payload }}`.
