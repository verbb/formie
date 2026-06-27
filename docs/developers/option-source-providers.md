# Custom Option Source Providers

Register lightweight server-resolved option lists for Dropdown, Radio, Checkboxes, and Recipients fields. Custom providers appear in the form builder as **Custom Provider** when they declare support for the current field usage.

Use this API when you need local Craft data — such as entries, categories, or users — without building a full Formie integration.

::: tip
For a full module walkthrough with a complete provider class, see [Creating a custom option source provider](/guides/fields/creating-a-custom-option-source-provider).
:::

## Register a provider

Listen for `OptionSources::EVENT_REGISTER_OPTION_SOURCE_PROVIDERS` in your module's `init()` method and push one or more provider classes onto the event:

```php
use verbb\formie\events\RegisterOptionSourceProvidersEvent;
use verbb\formie\services\OptionSources;
use yii\base\Event;

Event::on(OptionSources::class, OptionSources::EVENT_REGISTER_OPTION_SOURCE_PROVIDERS, function(RegisterOptionSourceProvidersEvent $event) {
    $event->providers[] = ClubRecipientsProvider::class;
});
```

Each provider class must implement `OptionSourceProviderInterface`.

## Provider contract

| Method | Purpose |
| --- | --- |
| `handle(): string` | Unique provider handle, stored on the field as `optionSource.provider`. |
| `displayName(): string` | Author-facing label in the form builder. |
| `usages(): string[]` | Supported usages: `options` for Dropdown/Radio/Checkboxes, `recipients` for Recipients fields. |
| `getBuilderConfig(array $params): array` | Builder UI config (`paramFields`, `defaults`, optional `warning`). |
| `resolveOptions(array $params, OptionSourceContext $context): OptionList` | Resolve `{ label, value }` rows at render and submit time. |

For Recipients fields, option values must be valid email addresses. Invalid rows are filtered automatically.

## Builder param fields

`getBuilderConfig()` uses the same `paramFields` shape as integration option sources:

- `handle` — stored in `optionSource.params`.
- `label` — author-facing field label.
- `type` — currently `select`.
- `options` — `[{ label, value }]` rows for the select input.
- `dependsOn` and `optionsByParam` — optional dependent selects.

Return `defaults` for any params that should be pre-filled when the provider is first selected.

See [Creating a custom option source provider](/guides/fields/creating-a-custom-option-source-provider) for a complete entry-backed Recipients example with dependent section/email-field selects.

## When to use integrations instead

Custom providers are ideal for local Craft data and simple server-side lookups. Use a [custom integration](/developers/custom-integration/option-sources) when you need OAuth, cached remote metadata, refresh workflows, or reusable provider fields shared across multiple forms.

## Guides

- [Creating a custom option source provider](/guides/fields/creating-a-custom-option-source-provider) — end-to-end walkthrough
- [Dynamic option sources in practice](/guides/fields/dynamic-option-sources-in-practice) — option source patterns
