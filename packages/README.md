# Headless Formie packages

Pick **one** primary package for your stack. Each installs what it needs (`@verbb/formie-core`, and for server-rendered forms `@verbb/formie-browser`) via dependencies, so you do not need to add those unless you are extending low-level APIs.

| Install | Use case |
|--------|----------|
| **`@verbb/formie-browser`** | Vanilla JS/ESM, Craft Twig templates, bundlers without React/Vue—HTML forms, captchas, GraphQL/REST render, `createFormieClient()`. |
| **`@verbb/formie-react`** | React apps: `<FormieForm />`, hooks, client-rendered forms (definition + form instance). |
| **`@verbb/formie-vue`** | Vue 3 apps: `<FormieForm />`, composables, client-rendered forms. |
| **`@verbb/formie-web-components`** | Custom elements: `formie-form` (server-rendered with browser behavior), `formie-core-form` (client-rendered forms), `registerFormieWebComponents()`. |

`@verbb/formie-core` is the shared **definition + form engine + transport** layer. App authors normally depend on it only indirectly.

## Monorepo development

Inside this workspace, package manifests use the same semver dependencies published to npm. After changing versions or `package.json`, run **`npm install`** from the workspace root so the lockfile and local workspace links stay current.

**Publishing to npm:** publish in dependency order: `@verbb/formie-core`, `@verbb/formie-browser`, then the framework packages.

## Styling Client-rendered Forms

Default page buttons are wrapped in **`formie-page-actions`** (React, Vue, and Lit). Target that class for submit/secondary button styling instead of framework-specific names.

## Fetch credentials

`@verbb/formie-core` and `@verbb/formie-browser` default to **`credentials: 'same-origin'`** so cross-origin dev (e.g. Vite on `localhost` → Craft with `Access-Control-Allow-Origin: *`) works. For **cookies across origins**, set **`credentials: 'include'`** on the transport options **and** configure Craft CORS (explicit origin, `Access-Control-Allow-Credentials: true`—not `*`).
