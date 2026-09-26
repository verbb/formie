# Build a Custom Module

Register a module in your application's trusted JavaScript bundle when a field needs additional browser behaviour. This example keeps a range input's output label in sync and removes its listener when the field disappears.

The field template must include `data-formie-field-uid` with the form-field instance UID, a range input and an output element. Formie's standard field wrapper supplies the UID attribute.

```ts
import { createFormieClient, type BrowserModuleDefinition } from '@verbb/formie-browser';

const rating: BrowserModuleDefinition = {
    moduleId: 'acme:rating',
    version: 1,
    surfaces: ['server-rendered'],
    kind: 'field',
    match: ({ target }) => Boolean(target.querySelector('input[type="range"]')),
    async setup({ target }) {
        const input = target.querySelector<HTMLInputElement>('input[type="range"]')!;
        const output = target.querySelector('output')!;
        const sync = () => { output.textContent = input.value; };
        input.addEventListener('input', sync);
        sync();
        return { destroy: () => input.removeEventListener('input', sync) };
    },
};

const client = createFormieClient();
client.registerModule(rating);
await client.scan(document);
```

Declare its configuration in your PHP field's `defineBrowserModules()` method:

```php
use verbb\formie\models\BrowserModuleEntry;

protected function defineBrowserModules(): array
{
    return [new BrowserModuleEntry([
        'key' => $this->uid . ':rating-primary',
        'moduleId' => 'acme:rating',
        'capability' => 'rating',
        'surfaces' => [BrowserModuleEntry::SURFACE_SERVER_RENDERED],
        'config' => ['max' => 10],
        'required' => true,
    ])];
}
```

Formie supplies the field target and includes the entry in the canonical manifest. Keep credentials and server-only settings out of `config`. The manifest cannot load a `src` URL; module code must already be registered or be resolved by Formie's trusted built-in import map.

For React, Vue and Web Components, register trusted definitions with `clientRenderedModuleRegistry.register(definition)` before mounting the component. The shared host owns reconciliation even when you replace the form component. Framework-independent consumers can pass their own `ModuleRegistry` to `mountClientRenderedModules(root, instance, registry)`. Declare `client-rendered` in both the PHP entry and executable definition only when the module supports that component's markup. Keep `<FormieForm>` for server-rendered forms and `<FormieClientForm>` for the structured client-rendered product.
