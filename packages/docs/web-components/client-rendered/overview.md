# Overview

Choose client-rendered forms when you want **`<formie-core-form>`** to load Formie’s client definition envelope and render the form UI inside the element, instead of mounting server-rendered HTML like `<formie-form>`.

If you want to see client-rendered forms in a fuller app setup, use the [Web Components starter](https://formie-starters.verbb.io/web-components) as a working example.

In this setup:

- the host renders from Formie’s client definition
- the front-end form behavior owns state, pages, validation, and submission
- you can swap **field** hosts, **field controls**, and some layout regions using **custom elements** registered on `FormieRegistry` (see [Component customization](/web-components/client-rendered/component-customization))

## Custom element

Start with a declarative host:

```html
<script type="module">
  import { registerFormieWebComponents } from '@verbb/formie-web-components';

  registerFormieWebComponents();
</script>

<formie-core-form
  form-handle="contactForm"
  endpoint="https://formie.test"
  transport="rest"
></formie-core-form>
```

You can set the same options from JavaScript (`element.transport = 'graphql'`, and so on). Known options are reflected as attributes where practical; complex values use properties only (for example `registry`).

> [!TIP]
> `<formie-core-form>` is built with [Lit](https://lit.dev/). You do not need to install or learn Lit to use the element in your app.


### Attributes and properties

| Name | Attribute | Type | Required | Description |
| --- | --- | --- | --- | --- |
| Form handle | `form-handle` | `string` | Yes | Handle of the form to load. |
| Endpoint | `endpoint` | `string` | Usually | REST: Craft base URL. GraphQL: GraphQL endpoint (often `/api` or absolute URL). An empty string is valid when the core client should resolve against the current origin. |
| Transport | `transport` | `'rest' \| 'graphql'` | No | Default `rest`. |
| Site | `site-id` | `number` | No | Request the form for a specific site. |
| Fetch credentials | `fetch-credentials` | `RequestCredentials` | No | `omit`, `same-origin`, or `include`. Default `same-origin`. |
| Form root class | `form-class` | `string` | No | Added to the rendered `<form>` root inside the host. |
| Loading copy | `loading-message` | `string` | No | Shown while the envelope loads. Default `Loading form…`. |
| Registry | *(property only)* | `FormieRegistry` | No | Per-instance overrides; defaults to `getFormieRegistry()`. |

### Instance API

After the element connects and loads, you can use:

| API | Description |
| --- | --- |
| `getFormieInstance()` | Returns `FrontendFormInstance \| null`. |
| `reload()` | Reloads the envelope and rebuilds the form instance (async). |

```html
<script type="module">
  const el = document.querySelector('formie-core-form');

  el?.addEventListener('formie:client:ready', () => {
    console.log('Form instance:', el.getFormieInstance());
  });
</script>
```

### Client events

The element re-dispatches core client events on the host (`bubbles` and `composed`):

- `formie:client:ready`
- `formie:submit:result`
- `formie:page:navigate`
- `formie:page:navigate:error`
- `formie:session:refreshed`
- `formie:session:refresh:error`
- `formie:state:reset`

Listen like any DOM `CustomEvent`; details match `@verbb/formie-core`.

## Transport

Use **REST** when you want the simplest envelope load and standard client-rendered controllers.

Use **GraphQL** when your stack already centers on GraphQL. In this mode Formie uses GraphQL for submit, session refresh, and page changes—not only the initial load.

## GraphQL query

Load `formieClientForm`:

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

Point `<formie-core-form transport="graphql" endpoint="…">` at your GraphQL HTTP endpoint. The element performs the envelope load using the same shape the core client expects.

## Manual GraphQL mutations

If you build your own client-rendered form with `@verbb/formie-core`, these are the mutations the transport layer uses:

- `submitFormieClientForm`
- `refreshFormieClientSession`
- `setFormieClientPage`

Example submit mutation:

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

When you use `<formie-core-form transport="graphql">`, the built-in transport calls these for you.

## Preloaded envelope

`<formie-core-form>` always loads the envelope from the network using `endpoint`, `form-handle`, and `transport`. To hydrate from a payload you already have, instantiate the form engine with `@verbb/formie-core` in your own module instead of this element, or keep using [server-rendered forms](/web-components/server-rendered/overview) with a preloaded `payload` on `<formie-form>` where that fits.
