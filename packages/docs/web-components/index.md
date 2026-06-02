# Web Components

Use `@verbb/formie-web-components` when you want portable custom elements around Formie: **server-rendered forms** (server-rendered form markup) or **client-rendered forms** (client-rendered UI in `<formie-core-form>`).

If you want to see the full Web Components integration in action, use the [Web Components starter](https://formie-starters.verbb.io/web-components) as a complete example app.

The package exposes two hosts:

- **`formie-form`** — server-rendered forms via `@verbb/formie-browser` (themes, server-rendered markup, browser events).
- **`formie-core-form`** — Definition + `@verbb/formie-core` form engine (REST/GraphQL envelope load, client-rendered field tree, optional registry overrides for custom elements).

If Craft is already rendering the final form HTML directly into the page and you do not need a custom element at the mount point, use the [Browser](/browser/) docs instead.

## Server-rendered Forms

Use server-rendered forms when:

- you want a plain HTML/JS embed and Formie-owned markup
- you want the browser package theme, events, and modules
- **`formie-form`** is enough for your mount story

Server-rendered forms come in these shapes:

- **Declarative** `<formie-form transport="rest" …>` (attributes and/or properties)
- **Imperative** `document.createElement('formie-form')` and set properties
- **`createFormieClient()`** when you need the full browser client without the `formie-form` wrapper

Those are the same server-rendered browser behavior, not different products.

Start with [Server-rendered](/web-components/server-rendered/overview).

## Client-rendered Forms

Use client-rendered forms when:

- you want `<formie-core-form>` to render the form UI
- you are fine styling that output yourself (or reusing class names from the default renderer)
- you may replace pieces with **custom elements** via `FormieRegistry` (whole **field** hosts, field controls only, optional layout regions)

The element loads the client definition envelope from `endpoint` + `form-handle` (REST or GraphQL). It does not currently accept a preloaded envelope attribute; for fully custom loading, use `@verbb/formie-core` in JavaScript.

Start with [Client-rendered forms](/web-components/client-rendered/overview).

## Transport

### Server-rendered forms (`formie-form`)

Use **REST** when you want the simplest path: Craft base URL or render URL, standard browser submit after paint.

Use **GraphQL** when the initial HTML payload should come from your GraphQL layer. Submit still uses the rendered form POST unless you change strategy at the browser layer.

Set `transport` and `endpoint` on `<formie-form>` (or as properties). For object options such as `themeConfig` or a preloaded `payload`, use **JavaScript properties** on the element or `createFormieClient()`.

### Client-rendered forms (`formie-core-form`)

Use **REST** when you want the default client definition envelope load and submit flow against Formie’s frontend actions.

Use **GraphQL** when the envelope and mutations should go through GraphQL; Formie uses GraphQL for submit, session refresh, and page changes when `transport="graphql"`.

See [Client-rendered forms — Overview](/web-components/client-rendered/overview) for the GraphQL query shape and mutation names.
