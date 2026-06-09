# Integration Option Sources

Integration option sources let an integration expose cached provider fields as selectable option lists for Dropdown, Radio and Checkboxes fields.

Formie resolves options through the shared `OptionSources` service. Integrations opt in by declaring one or more sources on the integration class. No separate event registration is required beyond the integration class itself.

Built-in examples:

- Mailchimp — `mailchimp-interests`
- HubSpot — `hubspot-forms`, `hubspot-properties`
- Salesforce — `salesforce-picklists`
- Zoho — `zoho-picklists`
- Microsoft Dynamics 365 — `dynamics365-picklists`

## How it works

1. The integration fetches provider metadata in `fetchFormSettings()` and caches it through `getFormSettings()`.
2. The integration declares option sources in `defineOptionSources()`.
3. Authors configure a Dropdown, Radio or Checkboxes field with **Options → Integration**, pick the integration instance, then choose the collection and remote field that supplies options.
4. At render and submit time, Formie resolves `{ label, value }` rows from the cached `IntegrationField` options.

Authors refresh integration data from the field settings UI or from the form’s **Integrations** tab. Option sources always read from the cache; they do not call the provider on every page view.

## Declaring sources

Override `defineOptionSources()` on your integration class and return one or more source definitions:

```php
protected static function defineOptionSources(): array
{
    return [
        [
            'handle' => 'example-lists',
            'label' => Craft::t('formie', 'List Fields'),
            'collectionKey' => 'lists',
            'storage' => 'collections',
            'collectionLabel' => Craft::t('formie', 'List'),
            'collectionInstructions' => Craft::t('formie', 'Choose the list that owns the field.'),
            'remoteHandleLabel' => Craft::t('formie', 'Option Source'),
            'remoteHandleInstructions' => Craft::t('formie', 'Choose which remote field supplies the options.'),
            'emptyCollectionsWarning' => Craft::t('formie', 'No lists available. Refresh the integration data first.'),
            'emptySourcesWarning' => Craft::t('formie', 'No option sources available. Refresh the integration data first.'),
        ],
    ];
}
```

Each definition must include a unique `handle` and author-facing `label`. The handle is stored on the field as `optionSource.provider`.

## Storage shapes

Formie supports two cached storage shapes.

### Collections (`storage: collections`)

Use when `fetchFormSettings()` returns an array of `IntegrationCollection` instances under a settings key.

| Setting | Default | Purpose |
| --- | --- | --- |
| `collectionKey` | `lists` | The `IntegrationFormSettings` key that holds collections |
| `storage` | `collections` | Read `IntegrationCollection[]` |

Email marketing integrations commonly use `collectionKey: lists`. HubSpot forms use `collectionKey: forms`.

Each collection’s `fields` should include `IntegrationField` instances with an `options` array when they can supply option rows.

### Objects (`storage: objects`)

Use when `fetchFormSettings()` returns flat `IntegrationField[]` arrays keyed by CRM object, such as `contact`, `lead` or `deal`.

| Setting | Purpose |
| --- | --- |
| `objectKeys` | Which settings keys to expose as selectable collections |
| `objectLabels` | Author-facing labels for each object key |
| `optionSourceTypes` | Optional list of `IntegrationField::$sourceType` values to include (for example Salesforce `picklist` and `multipicklist`) |

Example:

```php
protected static function defineOptionSources(): array
{
    return [
        [
            'handle' => 'example-picklists',
            'label' => Craft::t('formie', 'Picklists'),
            'storage' => 'objects',
            'objectKeys' => ['contact', 'deal'],
            'objectLabels' => [
                'contact' => Craft::t('formie', 'Contact'),
                'deal' => Craft::t('formie', 'Deal'),
            ],
            'optionSourceTypes' => ['picklist'],
        ],
    ];
}
```

Only fields with a non-empty `options` value are offered in the builder. Nested option groups are flattened automatically.

## Integration field options

Populate `IntegrationField::$options` the same way you do for field mapping pickers. Formie accepts flat rows or nested groups:

```php
new IntegrationField([
    'handle' => 'interestCategories',
    'name' => Craft::t('formie', 'Interest Categories'),
    'sourceType' => 'picklist',
    'options' => [
        'label' => Craft::t('formie', 'Interests'),
        'options' => [
            ['label' => 'Group A', 'value' => 'a'],
            ['label' => 'Group B', 'value' => 'b'],
        ],
    ],
]),
```

Set `sourceType` when you want to filter selectable fields with `optionSourceTypes`.

## Stored field config

Dynamic integration sources persist on the field as:

```json
{
  "optionsMode": "dynamic",
  "optionSource": {
    "type": "integration",
    "provider": "example-picklists",
    "params": {
      "integrationId": 123,
      "collectionId": "contact",
      "remoteHandle": "LeadSource"
    }
  }
}
```

- `integrationId` — the enabled integration instance
- `collectionId` — list ID, form ID, or object key depending on storage shape
- `remoteHandle` — the provider field handle that owns the options

Submissions store `{ value, label }` for dynamic integration fields.

## Resolver pipeline

You usually do not call the resolver directly. For reference:

- `IntegrationOptionSourceHelper::resolveOptions($provider, $params)` resolves rows from cached settings.
- `IntegrationOptionSourceResolver` handles fields implementing `OptionSourceFieldInterface`.
- `OptionSources::EVENT_REGISTER_OPTION_SOURCE_RESOLVERS` registers additional resolvers if needed.

Validation uses strict `in` checking against the resolved list unless the field uses **Template** mode.

## Custom builder or resolve behaviour

The base `Integration` class implements:

- `getOptionSourceBuilderConfig()`
- `resolveOptionSourceOptions()`

Override these when a provider needs bespoke collection grouping or lookup logic beyond `collectionKey`, `storage: objects` and `optionSourceTypes`.
