# React

Use `@verbb/formie-react` when React owns the form surface.

If you want to see the full React integration in action, use the [React starter](https://formie-starters.verbb.io/react) as a complete example app.

Start with the component path first:

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

The package has two rendering paths:

- server-rendered forms, where Formie still owns the rendered HTML and browser behavior
- client-rendered forms, where React renders from Formie's client definition

If Craft is already rendering the final form HTML directly into the page and React is only wrapping the surrounding screen, use the [Browser](/browser/) docs instead.

## Server-rendered Forms

Use server-rendered forms when:

- you want the fastest, lowest-effort path to a working React form
- you want Formie to keep owning the rendered markup, validation, and submit behavior
- you want to keep the browser package theme, events, and modules

Server-rendered forms come in two shapes:

- `<FormieForm transport="..." />` for the simplest path
- `<FormieForm source={{ payload }} />` when your app already fetched the HTML payload

Use `useFormieHtml()` only when you need lower-level ref ownership or imperative submit access.

Start with [Server-rendered](/react/server-rendered/overview).

## Client-rendered Forms

Use client-rendered forms when:

- React should render the form UI instead of mounting Formie-owned HTML
- you are happy to take on more implementation work in exchange for full UI control
- you need `components`, `fieldComponents`, or `slots` on `<FormieClientForm />`
- you want form hooks such as `useFormie()` and `useFormieField()`

In this setup, React renders from Formie's client definition and Formie owns state and submission behind it.

Start with [Client-rendered mode](/react/client-rendered/overview).

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
