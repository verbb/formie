# Field Adapters

An adapter connects an existing Craft field to Formie’s built-in **Custom Field** field type. It supplies the rendering and value handling Formie needs to submit, export, and integrate that field’s content. Start with the [Custom Field overview](/developers/custom-field/overview) for the core field concepts, then use this page when you need to support a Craft field through an adapter.

::: tip
For a walkthrough, see [Bringing your Craft field into Formie (Custom Field adapters)](/guides/fields/bringing-your-craft-field-into-formie-custom-field-adapters).
:::

Each adapter is responsible for:

- declaring the Craft field classes it supports,
- defining adapter-specific form-builder settings,
- rendering server-rendered and control panel submission inputs,
- normalizing and serializing submitted values,
- returning string, data, browser input, reference, summary, export and integration values,
- declaring GraphQL content and mutation shapes,
- declaring client-rendered input metadata.

Adapters declare `valueType($field)` and implement `serializeValueForClientInput()` independently of `getValueAsData()`. Use `decodeValueFromStorage()` for trusted legacy shapes; it is never called for request input. The default browser method deliberately shares the adapter data shape; override it when browser inputs require a different representation.

Built-in adapters include:

- **Link** — URL, email, phone and SMS link values using Craft’s Link field concepts, available without an extra plugin. Element link types are intentionally omitted until a public element-picker adapter exists.
- **Address (Google Maps)** — structured map values when a supported Google Maps Craft field class is installed.
- **Maps** — structured map values when a supported Maps/SimpleMap Craft field class is installed.

## Register an Adapter

Register adapters with `CustomFields::EVENT_REGISTER_CUSTOM_FIELD_ADAPTERS` in your module’s `init()` method:

```php
use modules\formie\fields\ExampleCustomFieldAdapter;

use verbb\formie\events\RegisterCustomFieldAdaptersEvent;
use verbb\formie\services\CustomFields;

use yii\base\Event;

Event::on(CustomFields::class, CustomFields::EVENT_REGISTER_CUSTOM_FIELD_ADAPTERS, function(RegisterCustomFieldAdaptersEvent $event) {
    $event->adapters[] = ExampleCustomFieldAdapter::class;
});
```

Adapters must implement `verbb\formie\fields\custom\CustomFieldAdapterInterface`. Most adapters should extend `verbb\formie\fields\custom\AbstractCustomFieldAdapter` and override only the pieces that differ from the scalar default.

```php
namespace modules\formie\fields;

use verbb\formie\fields\CustomField;
use verbb\formie\fields\custom\AbstractCustomFieldAdapter;
use verbb\formie\helpers\SchemaHelper;

use Craft;

class ExampleCustomFieldAdapter extends AbstractCustomFieldAdapter
{
    public static function handle(): string
    {
        return 'example';
    }

    public static function displayName(): string
    {
        return Craft::t('site', 'Example');
    }

    public static function craftFieldClasses(): array
    {
        return [
            'modules\\fields\\ExampleField',
        ];
    }

    public function getFormBuilderSettingsSchema(CustomField $field): array
    {
        return [
            SchemaHelper::textField([
                'label' => Craft::t('site', 'Placeholder'),
                'name' => $this->settingName('placeholder'),
            ]),
        ];
    }
}
```

`craftFieldClasses()` is advisory metadata and availability detection. It does not make arbitrary Craft fields work automatically. If the field needs custom JavaScript, structured storage or provider-specific formatting, implement those methods on the adapter.

Adapter-owned settings should be stored under `customFieldAdapterSettings` by using `settingName()` in schema nodes and `getSetting()` when reading values. This keeps the base Custom Field contract stable as adapters add provider-specific settings:

```php
SchemaHelper::textField([
    'label' => Craft::t('site', 'Placeholder'),
    'name' => $this->settingName('placeholder'),
]);

$placeholder = $this->getSetting($field, 'placeholder');
```

## Value Storage

Custom Field uses JSON storage so adapters can support scalar and structured values through one Formie field type. Scalar adapters can return strings; structured adapters can return arrays or value objects and decide how those values appear in references, exports and integrations.

The adapter is selected when the field is created and is not editable afterward. Treat the adapter class stored in `customFieldAdapter` as part of the field’s storage contract.

The selected adapter’s builder settings are stored in `customFieldAdapterSettings`. The adapter owns the meaning of those settings, including defaults used by `getDefaultValue()`, server-rendered markup and client input metadata.

When an adapter supports a structured value, implement these methods together:

- `normalizeValue()`
- `serializeValueForDb()`
- `isValueEmpty()`
- `getValueAsString()`
- `getValueAsData()`
- `getContentGqlType()`
- `getContentGqlMutationArgumentType()`

This keeps server-rendered submissions, GraphQL submissions, email summaries, exports and integrations aligned.

