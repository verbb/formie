# Rendering Products and Request Profiles

Formie can render forms in two ways. In **server-rendered** forms, Formie/Craft produces the HTML; `@verbb/formie-browser` enhances it. In **client-rendered** forms, your framework produces markup from Formie’s structured definition; `@verbb/formie-core` owns values, validation, conditions, navigation and submission state. Browser modules can add behaviour to either kind of form.

Use `<FormieForm>` for server-rendered React/Vue forms and `<FormieClientForm>` for client-rendered forms. Web Components use `<formie-form>` and `<formie-client-form>`. The browser package mounts server-rendered forms with `mode: 'server-rendered'`.

## HTML sources and submission transports

For a server-rendered component, `transport` chooses whether REST or GraphQL loads the HTML. In either case, the rendered form submits to Formie’s normal form endpoint.

Client-rendered forms can both load and submit through REST or GraphQL. Their results describe validation errors, page changes and payment status. Check request errors separately for malformed requests, denied access or server failures. Administrative GraphQL mutations are separate from these visitor-facing forms.

## Versioned bootstrap

`loadClientFormBootstrap()` and `loadGraphqlClientFormBootstrap()` return one `ClientFormBootstrap`:

```ts
{
    contractVersion: 1,
    definition: {
        id, handle, siteId,
        pages,       // rows, field instance IDs/UIDs, safe inputs and initial values
        settings,    // public validation/navigation configuration
        submission,  // staged upload endpoint
        modules: { contractVersion: 2, surface: 'client-rendered', entries: [] },
    },
    session,         // current page, version, tokens and scoped continuation
}
```

Update Formie and its npm packages together. If their data versions are incompatible, loading stops with a compatibility error; changing `contractVersion` manually will not resolve the mismatch.

Use the returned public definition. Do not build it from `getCpEditConfig()` or a provider’s full settings: those can contain server-only configuration. Providers expose the browser settings they need separately, including public site or publishable keys.

REST and GraphQL return the same submission result fields. A completed submission includes `completion`, which tells the adapter whether to show a message, redirect, reload or reset the form. Page changes, draft saves and pending payments do not run that completion action.

## Request profiles

| Profile | When to Use It |
| --- | --- |
| `same-origin-browser` | The form and Craft share an origin. Uses cookies and CSRF protection; this is the default. |
| `cross-origin-public` | The form runs on another allowed origin. Uses a Formie session without sending login cookies. |
| `trusted-administrative` | Server-side administrative mutations with Craft/API permissions. Not available to public form components. |

Set `profile` on React/Vue sources or component props. Web Components use `request-profile="cross-origin-public"`; manual browser mounts use `profile: 'cross-origin-public'`. Cross-origin server-rendered enhancement uses Ajax so it can carry the opaque session header to the normal form endpoint.

Custom `ClientTransport` implementations declare `browserRequestOptions` with their profile and optional opaque public session. `ClientFormInstance.getBrowserRequestOptions()` exposes that configuration to the shared module host, which supplies the current CSRF context to provider requests. Profiles are not inferred from endpoint URLs. Address lookups and payment initialization use the same request policy as the form.

Configure Formie separately from Craft’s GraphQL CORS settings:

```php
// config/formie.php
return [
    'allowedOrigins' => ['https://forms.example.com'],
];
```

A cross-origin form receives an `X-Formie-Session` header when it loads. The standard adapter sends it on later requests, including uploads. Reload the form if the session expires. Configuring CORS alone is not sufficient; the origin must also be allowed by Formie. Never put an administrative API token in browser code.

## Uploads

Selected `File`/`Blob` values use the staged multipart upload endpoint and become `{ uploadUid, attachToken }` values before REST/GraphQL submission. File bytes do not normally travel inside JSON. Existing explicit Base64 input is compatibility-only. Upload capabilities remain bound to the form, field and visitor context established by the backend.

## Browser modules

Browser modules provide behaviour such as date pickers, CAPTCHA widgets and file uploads. Both rendering approaches use the modules declared by the form. If a required module cannot load, submission is blocked and the visitor sees an error.

For custom modules, follow [Build a Custom Module](/browser/modules/build-a-custom-module). The [module reference](/browser/modules/) explains configuration, repeated fields and cleanup when a field disappears.

Browser stages are `prepare`, `validate`, `challenge`, `payment`, `send` and `result`. Use [JavaScript Events](/browser/behavior/javascript-events) to observe them; they are separate from the PHP submission workflow.
