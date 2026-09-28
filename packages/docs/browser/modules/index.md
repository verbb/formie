# Browser Modules

Browser modules add features such as date pickers, CAPTCHA widgets and file uploads. Formie includes the modules each form needs. Use this reference when declaring modules for a custom field or building a custom client.

A `BrowserModuleDefinition` describes trusted executable code registered in your JavaScript bundle. Its namespaced `moduleId`, such as `formie:date-picker` or `acme:rating`, identifies that code. A `BrowserModuleEntry` configures one occurrence. A `BrowserModuleInstance` holds one mounted occurrence and its cleanup. `BrowserModuleManifest` names the complete wire collection.

## Manifest Contract

The `data-formie-modules` attribute and client-rendered definition use this structure:

```json
{
  "contractVersion": 2,
  "surface": "client-rendered",
  "entries": [{
    "key": "rating-primary",
    "moduleId": "acme:rating",
    "kind": "field",
    "targets": [{"type": "field", "uid": "form-field-instance-uid"}],
    "config": {"max": 10},
    "required": true
  }]
}
```

The entry key identifies the declaration, independently of its configuration. Declare separate keys when the same field needs the same module twice. The PHP builder assigns occurrence keys to declarations without an explicit key. Repeated declarations are retained even when their configurations are identical.

Field targets use form-field instance UIDs. Other discriminated targets address the form, a page ID, a form action or an explicit selector. Targets are scoped to the mounted form, and executable URLs are not accepted. Register custom code in your trusted bundle as shown in [Build a custom module](/browser/modules/build-a-custom-module).

Each manifest contains exactly one `surface`: `server-rendered`, `client-rendered` or `cp-edit`. PHP declarations list their supported surfaces; Formie filters and completes those declarations when it projects a manifest. A declaration that omits surfaces applies to server-rendered forms. CP configuration stays separate from the public field definition.

Client-rendered field definitions reference exact entry keys through `moduleRefs`. Consumers resolve only those keys; `moduleId` identifies reusable executable code and is not an occurrence reference.

## Dynamic Lifecycle and Failures

The runtime tracks each entry key and matching DOM element. Repeater rows and conditional fields mount when they appear and dispose when removed or hidden. Repeated reconciliation does not mount an unchanged occurrence twice. `hydrateFormieModules()` exposes `update(manifest)` for configuration changes and removed declarations; an instance can implement `update(context)` or be disposed and mounted again.

A required module failure blocks submission and displays an actionable message. An optional failure emits a diagnostic and lets unrelated fields continue. Diagnostics include the entry key, module ID, surface and failure code, without publishing secret configuration. Unsupported manifest versions fail before module execution. Use matching Formie and npm package versions when deploying.

Framework adapters preserve every entry and its occurrence identity. Modules can expose stable `beforeSubmit` and `afterSubmit` hooks without depending on Formie's internal submission-stage names. `beforeSubmit` can stop dispatch through its abort helpers; `afterSubmit` observes a result already returned to the browser and cannot retroactively abort it. Native browser submissions run `beforeSubmit`, then navigate without a browser-visible result, so `afterSubmit` applies to Ajax and client-rendered submissions. Other declarations pass through the same trusted browser registry and lifecycle.
