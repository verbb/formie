# Overview

Choose client-rendered forms when you want **`<formie-client-form>`** to build the form in the browser from Formie's field and page definitions. It loads these definitions and the submission session together in a response called the **envelope**. For a component that displays HTML rendered by Craft, use `<formie-form>` instead.

If you want to see client-rendered forms in a fuller app setup, use the [Web Components starter](https://formie-starters.verbb.io/web-components) as a working example.

In this setup:

- the host renders from Formie’s client definition
- the client-rendered engine owns state, pages, validation, and submission
- you can swap **field** hosts, **field controls**, and some layout regions using **custom elements** registered on `FormieRegistry` (see [Component customisation](/web-components/client-rendered/component-customization))

## Custom Element

Start with a declarative host:

```html
<script type="module">
  import { registerFormieWebComponents } from '@verbb/formie-web-components';

  registerFormieWebComponents();
</script>

<formie-client-form
  form-handle="contactForm"
  endpoint="https://formie.test"
  transport="rest"
></formie-client-form>
```

You can set the same options from JavaScript (`element.transport = 'graphql'`, and so on). Known options are reflected as attributes where practical; complex values use properties only (for example `registry`).

> [!TIP]
> `<formie-client-form>` is built with [Lit](https://lit.dev/). You do not need to install or learn Lit to use the element in your app.


### Attributes and Properties

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
| `getFormieInstance()` | Returns `ClientFormInstance \| null`. |
| `reload()` | Reloads the envelope and rebuilds the form instance (async). |

```html
<script type="module">
  const el = document.querySelector('formie-client-form');

  el?.addEventListener('formie:client:ready', () => {
    console.log('Form instance:', el.getFormieInstance());
  });
</script>
```

### Client Events

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

## GraphQL Query

Load `formieClientForm`:

```graphql
query ClientForm($handle: String!, $siteId: Int) {
  formieClientForm(handle: $handle, siteId: $siteId) {
    contractVersion
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

Point `<formie-client-form transport="graphql" endpoint="…">` at your GraphQL HTTP endpoint. The element performs the envelope load using the same shape the core client expects.

## Manual GraphQL Mutations

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

When you use `<formie-client-form transport="graphql">`, the built-in transport calls these for you.

## Preloaded Envelope

`<formie-client-form>` always loads the envelope from the network using `endpoint`, `form-handle`, and `transport`. To start with form data you have already loaded, instantiate the form engine with `@verbb/formie-core` in your own module instead of this element, or keep using [server-rendered forms](/web-components/server-rendered/overview) with a preloaded `payload` on `<formie-form>` where that fits.

## Completion and Query Prefill

A successful final submission returns `completion` with `behavior` (`message`, `redirect`, `reload` or `reset`), `url`, `target`, `message` and `hideForm`. The standard adapter applies it. Page navigation and save-for-later results have no completion action. Payment continuation remains a separate result. Custom renderers should use this result rather than infer completion from an absent next page.

Use the REST or GraphQL loader’s `query` option to pass selected URL parameters for prefilling fields or forwarding campaign values. Formie captures them when the form loads; changing them in a later submission does not replace the originals. This option supplies field input, not form settings or credentials.
