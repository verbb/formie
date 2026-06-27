# Bringing your Craft field into Formie (Custom Field adapters)

Your Craft site may already have field types from custom plugins — [Maps](https://plugins.craftcms.com/simplemap?craft5), [Google Maps](https://plugins.craftcms.com/google-maps?craft5), or your own field classes — or Craft's built-in Link field. Formie's **Custom Field** field type lets authors use those fields on forms without you building a full Formie field type for each one.

Each supported option is an **adapter** — explicit Formie code that handles front-end rendering, validation, storage, exports, integrations, and GraphQL for a specific Craft field class. This guide explains when an adapter is the right choice, how Formie's built-in adapters work, and how to register your own from a Craft module.

Read the [Custom Field reference](/fields/custom-field) and [Custom Field adapters (developer reference)](/developers/custom-field#custom-field-adapters) before implementing — adapters touch more surfaces than a typical Craft field because Formie must submit, export, and integrate reliably on the front end.

## Full field type vs adapter

| Approach | Use when |
| --- | --- |
| **Full Formie field** | You own the complete field behaviour and want a dedicated palette item. See [Creating a Formie field type from scratch](/guides/fields/creating-a-formie-field-type-from-scratch). |
| **Custom Field adapter** | A Craft field (yours or a third-party plugin's) already exists and you want Formie to bridge to it reliably. |

Adapters are opt-in because Formie needs more than a Craft field class name to submit reliably on the front end. `craftFieldClasses()` is advisory metadata for availability detection — it does not make arbitrary Craft fields work automatically.

## Built-in adapters

Formie ships three adapters today:

### Link

Bridges [Craft's built-in Link field](https://craftcms.com/docs/5.x/reference/field-types/link.html) for the link types that work on public forms without CP element selectors:

- URL
- Email
- Phone
- SMS

Entry, Asset, and Category link types are intentionally omitted until a public element-picker adapter exists.

When one link type is allowed, Formie renders a single value input and stores the type in a hidden field. When multiple types are enabled, a type selector updates the input's native type and keyboard hints as the user switches.

Authors add **Custom Field → Link** and configure allowed types, URL options, labels, and advanced attributes in the field settings modal.

### Maps (Simple Map)

Bridges the [Maps](https://plugins.craftcms.com/simplemap?craft5) plugin's map field when the plugin is installed. Stores structured address and coordinate data as JSON.

Adapter settings include address defaults, map visibility, latitude/longitude display, current-location behaviour, and zoom where the provider supports them.

### Address (Google Maps)

Bridges the [Google Maps](https://plugins.craftcms.com/google-maps?craft5) plugin's Address field when the plugin is installed. Appears in the Custom Field Type picker only when that plugin is active.

Both map adapters implement structured value methods together — `normalizeValue()`, `serializeValue()`, `getValueAsString()`, GraphQL types, and client input metadata — so front-end submissions, exports, and integrations stay aligned.

## Register an adapter from your module

When you need to bridge a Craft field Formie does not ship an adapter for, create an adapter class in your Craft module and register it at boot time.

Listen for `CustomFields::EVENT_REGISTER_CUSTOM_FIELD_ADAPTERS` in your module's `init()`:

```php
use modules\formie\fields\ExampleCustomFieldAdapter;
use verbb\formie\events\RegisterCustomFieldAdaptersEvent;
use verbb\formie\services\CustomFields;
use yii\base\Event;

Event::on(CustomFields::class, CustomFields::EVENT_REGISTER_CUSTOM_FIELD_ADAPTERS, function(RegisterCustomFieldAdaptersEvent $event) {
    $event->adapters[] = ExampleCustomFieldAdapter::class;
});
```

Implement `CustomFieldAdapterInterface`. Most adapters extend `AbstractCustomFieldAdapter` and override only what differs from the scalar default — you do not need every method on day one. Start with builder settings and front-end rendering, then add value normalisation and export helpers as your field requires structured data.

## Minimal adapter skeleton

The skeleton below shows the minimum to appear in the Custom Field Type picker. Expand it with rendering, validation, and value methods from the checklist further down.

```php
<?php
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

    public static function isAvailable(): bool
    {
        return class_exists('modules\\fields\\ExampleField');
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

After registration, **Example** appears in the Custom Field Type picker when `isAvailable()` returns true.

## Adapter-owned settings

Store settings under `customFieldAdapterSettings` using `settingName()` in schema nodes and `getSetting()` when reading values:

```php
SchemaHelper::textField([
    'label' => Craft::t('site', 'Placeholder'),
    'name' => $this->settingName('placeholder'),
]);

$placeholder = $this->getSetting($field, 'placeholder');
```

This keeps the base Custom Field contract stable as adapters add provider-specific options.

## What to implement

Start with the methods your Craft field actually needs:

| Concern | Methods |
| --- | --- |
| Form builder settings | `getFormBuilderSettingsSchema()`, `getFormBuilderPreviewSchema()` |
| Front-end rendering | `getInputTemplatePath()` or `renderInput()` if templates are not enough |
| Value normalisation | `normalizeValue()`, `serializeValue()`, `isValueEmpty()` |
| Output contexts | `getValueAsString()`, `getValueAsArray()`, `getValueForExport()`, `getValueForIntegration()` |
| GraphQL | `getContentGqlType()`, `getContentGqlMutationArgumentType()` |
| Client-rendered forms | `getClientInputDefinition()`, `getClientModules()` |

For structured values (maps, addresses, multi-part data), implement the value and GraphQL methods together so every output path sees the same shape.

## Storage contract

The adapter is chosen when the field is created and **cannot be changed afterward** — each adapter may store a different value shape. Treat `customFieldAdapter` and `customFieldAdapterSettings` as part of the field's storage contract.

Custom Field uses JSON storage so adapters can support both scalar strings and structured arrays through one Formie field type.

## Testing checklist

1. Add **Custom Field** to a form and pick your adapter type.
2. Configure adapter settings and save the form.
3. Submit from the front end — confirm the value persists on the submission.
4. Check email notification summaries, exports, and any enabled integrations.
5. If you support GraphQL, confirm the mutation argument type matches what clients send.

## Related

- [Custom Field](/fields/custom-field)
- [Custom Field adapters](/developers/custom-field#custom-field-adapters)
- [Creating a Formie field type from scratch](/guides/fields/creating-a-formie-field-type-from-scratch)
