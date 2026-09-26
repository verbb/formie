# Rendering Products and Request Profiles

Formie supports two markup products. In **server-rendered** forms, Formie/Craft produces the HTML; `@verbb/formie-browser` enhances it. In **client-rendered** forms, your framework produces markup from Formie’s structured definition; `@verbb/formie-core` owns values, validation, conditions, navigation and submission state. Browser modules can participate in either product. CP edit is the separate control-panel submission editing surface.

Use `<FormieForm>` for server-rendered React/Vue forms and `<FormieClientForm>` for client-rendered forms. Web Components use `<formie-form>` and `<formie-client-form>`. The browser package accepts only `mode: 'server-rendered'` and rejects the old client-rendered mount mode.

## HTML sources and submission transports

REST and GraphQL are transports, not rendering products. The server-rendered component’s `transport` option selects its **HTML source**. Both sources return Craft-produced HTML, and that HTML always submits to Formie’s normal form endpoint. GraphQL-sourced HTML does not acquire a GraphQL submission path.

The client-rendered engine has REST-style and interactive public GraphQL adapters. Both call the same backend submission authority and workflow. Expected validation, navigation and payment states are results; malformed requests, unauthorized requests and system failures are transport errors. Administrative GraphQL mutations retain their separate schema permissions and authority.

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
        modules: { contractVersion: 1, entries: [] },
    },
    session,         // current page, version, tokens and scoped continuation
}
```

All five npm packages enforce their applicable versioned contracts before rendering or loading modules. PHP rejects unsupported versions too. Update Formie and its packages together when the compatibility error appears. Do not remove the version check or coerce a future contract into version 1.

CP configuration, PHP value class names, raw integration settings, payment provider settings and secrets are not public field definitions. Integrations expose an explicit safe browser configuration, which may include public site/publishable keys. Never copy `getCpEditConfig()` or a provider’s full settings into the bootstrap.

REST and GraphQL client results share `outcome`, submission identity/version, navigation, exact errors, messages, session, payment, questionnaire and browser events where applicable. `completion` and `redirect` are nullable extension seams; their presence does not add new completion or dynamic form-setting behavior.

## Request profiles

| Profile | Authority and state |
| --- | --- |
| `same-origin-browser` | Cookie-backed session, CSRF, Formie request tokens and scoped continuation credentials. Default for browser adapters. |
| `cross-origin-public` | An exact Formie `allowedOrigins` match, no ambient login authority, an opaque Formie session and scoped grants. Requests omit cookies. |
| `trusted-administrative` | Craft/API authentication plus schema/user permissions, through administrative mutations; public form adapters reject this profile. |

Set `profile` on React/Vue sources or component props. Web Components use `request-profile="cross-origin-public"`; manual browser mounts use `profile: 'cross-origin-public'`. Cross-origin server-rendered enhancement uses Ajax so it can carry the opaque session header to the normal form endpoint.

Custom `ClientTransport` implementations declare `browserRequestOptions` with their profile and optional opaque public session. `ClientFormInstance.getBrowserRequestOptions()` exposes that configuration to the shared module host, which supplies the current CSRF context to provider requests. Profiles are not inferred from endpoint URLs. Address lookups and payment initialization use the same request policy as the form.

Configure Formie separately from Craft’s GraphQL CORS settings:

```php
// config/formie.php
return [
    'allowedOrigins' => ['https://forms.example.com'],
];
```

Origins are checked before mutation. CORS response headers alone do not authorize a request. Cross-origin public bootstrap returns `X-Formie-Session`; the shared HTTP adapter carries it on later requests, including uploads. Expired credentials require reloading the form. Grant exchange and backend submission permissions remain necessary. Never put an administrative API token in a public browser bundle.

## Uploads

Selected `File`/`Blob` values use the staged multipart upload endpoint and become `{ uploadUid, attachToken }` values before REST/GraphQL submission. File bytes do not normally travel inside JSON. Existing explicit Base64 input is compatibility-only. Upload capabilities remain bound to the form, field and visitor context established by the backend.

## Browser modules

PHP produces one [browser-module manifest](/browser/modules/) for both public products. `BrowserModuleDefinition` is trusted executable metadata in the registry; `BrowserModuleEntry` is one configured occurrence; `BrowserModuleInstance` is one mounted occurrence; `BrowserModuleManifest` is the versioned collection.

Entries identify executable code using a namespaced `moduleId`, never an arbitrary `src` URL. Distinct `key` values preserve repeated declarations, including identical capabilities on one field. Field targets use form-field instance UIDs. Form, page, button and global targets have explicit target types. Missing surfaces default to server-rendered only.

The shared host reconciles repeated DOM targets, conditional visibility, updated configuration and removed declarations. Required loading or initialization failures block submission and show a safe message. Optional failures emit `formie:browser:module:error` diagnostics and leave unrelated interactions available. Diagnostics include the occurrence key, module ID, surface and required flag.

Framework-owned controls explicitly delegate conditions, repeater rows, signatures, file selection, checkbox/radio state, text limits and structured dates to core/native controls. Delegation emits a capability reason for each mounted declaration. Other modules use the same trusted loader. Custom provider controls must supply the module’s documented markup; a required module that cannot initialize blocks submission. A missing native renderer is not permission to omit its declaration.

Browser stages are `prepare`, `validate`, `challenge`, `payment`, `send` and `result`. They describe browser behavior and do not expose backend workflow stages.

## Formie 4 beta migration

| Previous beta API | Current API |
| --- | --- |
| `getClientConfig()` | `getCpEditConfig()` |
| `getClientPayload()` | `getClientRenderedDefinition()` |
| `getClientInputDefinition()` / `defineClientInput()` | `getClientRenderedInput()` / `defineClientRenderedInput()` |
| `getClientConditions()` | `getBrowserConditions()` |
| `clientChildren()` | `clientRenderedChildren()` |
| `ClientModule`, `clientModules()` | `BrowserModuleEntry`, `browserModules()` |
| Module `id`, `frontend`, `src` | `moduleId`, `surfaces`, trusted registry registration |
| `FrontendFormEnvelope` | `ClientFormBootstrap` |
| `FrontendFormDefinition`, `FrontendFormSession`, `FrontendFormInstance` | `ClientFormDefinition`, `ClientFormSession`, `ClientFormInstance` |
| `FrontendSubmitResult` | `ClientSubmitResult` |
| `loadFrontendEnvelope()` | `loadClientFormBootstrap()` |
| `schemaVersion` | `contractVersion` |
| `<formie-core-form>` | `<formie-client-form>` |

CP edit configuration now lives in `FieldCpEditConfigTrait`; public field definition/input assembly lives in `FieldClientRenderedDefinitionTrait`. Beta aliases are not retained.

Verified stable Formie 3 `getFrontEndJsModules()` field declarations have a deprecated adapter. Repeated entries survive. Built-in names map to trusted Formie module IDs; third-party names require registration under `legacy:<kebab-name>`. Legacy `src` values are never executed. Stable Twig asset registration and the documented legacy DOM/validator event adapters remain available. This is a bounded compatibility bridge, not a promise that every Formie 3 third-party script runs without migration.
