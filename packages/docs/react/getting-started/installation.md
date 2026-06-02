# Installation

Install `@verbb/formie-react` into your app:

```bash
npm install @verbb/formie-react
```

`@verbb/formie-react` has peer dependencies on React 19 and React DOM 19.

## CSS

If you are using server-rendered forms and want the shipped Formie UI, also import the browser CSS:

Those CSS entrypoints come from `@verbb/formie-browser`, which is already a dependency of `@verbb/formie-react`.

```tsx
import '@verbb/formie-browser/css/formie.css';
```

You can split that into base and theme layers if your app needs separate control:

```tsx
import '@verbb/formie-browser/css/formie-base.css';
import '@verbb/formie-browser/css/formie-theme.css';
```

Client-rendered forms do not require the browser theme. Only import it if you intentionally want to reuse Formie's shipped styling.

## Browser requirements

- server-rendered forms need an `endpoint`, a `formHandle`, and either `transport="rest"` or `transport="graphql"` unless you pass `source={{ payload }}` directly.
- client-rendered forms need either a `source` definition or both `endpoint` and `formHandle`.
- For REST, pass the Craft base URL. Formie resolves the browser action endpoints for you.
- For GraphQL, pass the GraphQL endpoint directly. On many installs that is `/api`.
- If your frontend and Craft live on different domains, point `endpoint` at the Craft site, not the frontend app.
- If Craft control panel lives under something like `/admin`, do not use the CP URL. Use the Craft site root for REST, or the GraphQL endpoint for GraphQL.

## React app notes

- In Next.js or other React Server Component setups, keep `@verbb/formie-react` usage inside client components.
- If your app preloads payloads or client definition envelopes in a route loader, pass them into React instead of fetching again inside the form component.

## Next step

Choose the right mode on [React](/react/).
