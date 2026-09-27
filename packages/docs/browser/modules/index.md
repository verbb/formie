# Browser Modules

Browser modules add features such as date pickers, CAPTCHA widgets and file uploads. Formie includes the modules each form needs. Use this reference when declaring modules for a custom field or building a custom client.

A `BrowserModuleDefinition` describes trusted executable code registered in your JavaScript bundle. Its namespaced `moduleId`, such as `formie:date-picker` or `acme:rating`, identifies that code. A `BrowserModuleEntry` configures one occurrence. A `BrowserModuleInstance` holds one mounted occurrence and its cleanup. `BrowserModuleManifest` names the complete wire collection.

## Manifest Contract

The `data-formie-modules` attribute and client-rendered definition use this structure:

```json
{
  "contractVersion": 1,
  "entries": [{
    "key": "rating-primary",
    "moduleId": "acme:rating",
    "type": "field",
    "capability": "rating",
    "surfaces": ["server-rendered", "client-rendered"],
    "targets": [{"targetType": "field", "targetId": "form-field-instance-uid"}],
    "config": {"max": 10},
    "required": true
  }]
}
```

The entry key identifies the declaration, independently of its configuration. Declare separate keys when the same field needs the same module twice. The PHP builder assigns occurrence keys to declarations without an explicit key. Repeated declarations are retained even when their configurations are identical.

Field targets use form-field instance UIDs. Form, page, button and global targets use explicit `targetType` values and their corresponding `targetId`. Targets are scoped to the mounted form; arbitrary selectors and executable URLs are not accepted. Register custom code in your trusted bundle as shown in [Build a custom module](/browser/modules/build-a-custom-module).

Surfaces are `server-rendered`, `client-rendered` and `cp-edit`. A declaration that omits surfaces applies to server-rendered forms. Explicitly add other surfaces after verifying the module supports their markup and lifecycle. CP configuration stays separate from the public field definition.

## Dynamic Lifecycle and Failures

The runtime tracks each entry key and matching DOM element. Repeater rows and conditional fields mount when they appear and dispose when removed or hidden. Repeated reconciliation does not mount an unchanged occurrence twice. `hydrateFormieModules()` exposes `update(manifest)` for configuration changes and removed declarations; an instance can implement `update(context)` or be disposed and mounted again.

A required module failure blocks submission and displays an actionable message. An optional failure emits a diagnostic and lets unrelated fields continue. Diagnostics include the entry key, module ID, surface and failure code, without publishing secret configuration. Unsupported manifest versions fail before module execution. Use matching Formie and npm package versions when deploying.

Framework adapters explicitly delegate capabilities already owned by their controls or the core state engine, such as repeater values, conditions and staged uploads. They preserve every entry and its occurrence identity. Other declarations pass through the same trusted browser registry and lifecycle.
