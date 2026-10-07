# Custom Field

You can add your own fields to Formie in two ways: register a full Formie field type, or register an adapter for the built-in **Custom Field** field type when you want to expose a Craft field through Formie.

Use a full Formie field when you own the complete field behaviour. Use a Custom Field adapter when you want one Formie field type to bridge to a Craft field or plugin-provided Craft field with explicit support for server-rendered markup, value handling, exports, integrations and GraphQL.

## Register a Formie Field Type

Register a Formie field type when the field should appear as its own item in the form builder palette.

```php
use modules\ExampleField;

use verbb\formie\events\RegisterFieldsEvent;
use verbb\formie\services\Fields;
use yii\base\Event;

Event::on(Fields::class, Fields::EVENT_REGISTER_FIELDS, function(RegisterFieldsEvent $event) {
    $event->fields[] = ExampleField::class;
    // ...
});
```

Fields must extend `verbb\formie\base\Field`. This gives your field the form-builder schema, server-rendered markup, value handling, optional client-rendered configuration, and reference / reference-block rendering behaviour Formie expects.

For fields that contain other fields, extend the parent field class that matches the behaviour you need:

- `ParentField` for editable nested fields.
- `FixedParentField` for fields with a fixed set of child fields, such as Name, Address or Date/Time.
- `RepeatableParentField` for fields that repeat a group of nested fields.

Formie fields are Formie components, not Craft custom fields. Some concepts will feel familiar if you have built Craft fields before, but use Formie’s field base classes and methods rather than Craft’s `Field` class. Refer to the [Field](/reference/field) object documentation for the data available on a field instance.

::: tip
There is also a [step-by-step guide for creating a Formie field type](/guides/fields/creating-a-formie-field-type-from-scratch) that walks through building a field from scratch.
:::

## Methods

::: reference
### `displayName()`

**Returns:** `string`

Returns the name to be used for the field.
:::

::: reference
### `getInputTemplatePath()`

**Returns:** `string`

Returns the path to the server-rendered template for this field.
:::

::: reference
### `getReferenceBlockTemplatePath()`

**Returns:** `string`

Returns the path to the template used when the field is rendered as a reference block.
:::

::: reference
### `getSvgIconPath()`

**Returns:** `string`

Returns the path to the SVG icon used as the field type in the control panel.
:::

::: reference
### `defineFormBuilderPreviewSchema()`

**Returns:** `array`

Returns the preview schema shown in the form builder.
:::

::: reference
### `defineFormBuilderGeneralSchema()`

**Returns:** `array`

Defines the schema for the `General` tab in the field edit modal.
:::

::: reference
### `defineFormBuilderSettingsSchema()`

**Returns:** `array`

Defines the schema for the `Settings` tab in the field edit modal.
:::

::: reference
### `defineFormBuilderAppearanceSchema()`

**Returns:** `array`

Defines the schema for the `Appearance` tab in the field edit modal.
:::

::: reference
### `defineFormBuilderAdvancedSchema()`

**Returns:** `array`

Defines the schema for the `Advanced` tab in the field edit modal.
:::

::: reference
### `defineFormBuilderConditionsSchema()`

**Returns:** `array`

Defines the schema for the `Conditions` tab in the field edit modal.
:::

::: reference
### `supportedDefaults()`

Returns setting handles that can be configured as organisation-wide defaults in **Settings → Defaults**. See [Field Defaults](/developers/field-defaults).
:::

::: reference
### `defineBrowserValidationRules()`

Defines the validation rules sent to Formie’s browser assets.
:::

::: reference
### `defineRules()`

Defines server-side Yii validation rules for the field model.
:::

::: reference
### `defineClientRenderedInput()`

Adds input-specific configuration to the client-rendered field definition.
:::

::: reference
### `clientRenderedDefinition()`

**Returns:** `verbb\formie\fields\definitions\FieldClientRenderedDefinition`

Declares the base field type and input metadata used in the structured client-rendered payload.
:::

::: reference
### `clientRenderedChildren()`

**Returns:** `verbb\formie\fields\definitions\FieldClientRenderedChildren`

Declares whether the client-rendered payload is scalar, part-based, or row-based for nested fields.
:::

::: reference
### `defineBrowserModules()`

Declares browser modules with explicit supported surfaces and a required/optional failure policy.
:::

::: reference
### `browserModules()`

**Returns:** `verbb\formie\models\BrowserModule[]`

Returns immutable browser-module declarations completed for the current form, field and rendering surface.
:::

::: reference
### `defineReferenceValues()`

**Returns:** `verbb\formie\fields\definitions\FieldReferenceValue[]`

Declares the field values available to reference tokens, field selectors and the Variable Picker. Use `FieldReferenceValue::primary()` for the field’s natural value and `FieldReferenceValue::selector()` for deliberate projections such as an Address field’s city. Each declaration provides semantic types, inline or block shape, authoring availability and any structured setting condition; the same declaration is enforced when the token resolves.
:::

::: reference
### `referenceValues()`

**Returns:** `verbb\formie\fields\definitions\FieldReferenceValue[]`

Returns the validated reference declarations exposed by the field. Formie adds the default primary declaration when `defineReferenceValues()` does not provide one. Override `defineReferenceValues()` rather than this method.
:::

::: reference
### `conditions()`

**Returns:** `verbb\formie\conditions\ConditionSet`

Returns the normalized predicate used by server evaluation and the browser condition compiler. Browser markup and client-rendered definitions compile the same set. Resolved field predicates are scoped to the submission instance and repeater row; setting a field value invalidates that derived state. Use the submission's value setters after editing a value model rather than mutating cached values in place.
:::

::: reference
### `defineSlotTag()`

Defines the HTML tag and attributes used by `fieldtag()` slots in the field’s Twig template.
:::

::: reference
### `getInputTemplateVariables()`

**Returns:** `array`

Adds variables available to the server-rendered field template.
:::

::: reference
### `defineValueAsString()`

Defines the field value when Formie needs a string value.
:::

::: reference
### `defineValueAsData()`

Defines the natural JSON-safe value. Return useful scalars for primitive fields and explicit named data for structured fields.
:::

::: reference
### `defineValueForExport()`

Defines the field value used in exports.
:::

::: reference
### `defineValueForSummary()`

Defines the field value used in summaries.
:::

::: reference
### `defineValueForReference()`

Defines the field value used for singular reference contexts.
:::

::: reference
### `defineValueForReferenceBlock()`

Defines the field value used before rendering reference-block content.
:::

::: reference
### `defineValueForIntegration()`

Defines the field value used when sending data to integrations.
:::

::: reference
### `fieldKind()`

**Returns:** `string`

Defines the kind of field for the client input contract.
:::

::: reference
### `valueType()`

**Returns:** `verbb\formie\fields\definitions\FieldValueType`

Returns the post-normalisation PHP value type declared by the protected `defineValueType()` hook. Formie asserts this type when normalising submission content and publishes its schema in client metadata. It does not choose storage or public projections.
:::


Refer to the [Field](/reference/field) object documentation for more.

## Normalised Values and Projections

A field first normalises input into a consistent PHP value. It then converts that value for each use: display text, JSON data, database storage, exports or integration requests. These conversions are called **projections**. Keep them separate so, for example, changing a date’s display format does not change how it is stored.

Choose a runtime value that is useful to a template author. Text and Email return strings; Agree returns a boolean; Number retains a decimal string so PHP floats cannot round large values. Null is accepted as input, but each field defines its own empty result. Name always returns `NameFieldValue`. Phone returns `PhoneFieldValue` with the entered number, country, canonical E.164 number when valid, and dialling code. Date and option fields preserve their domain objects. Group and Repeater compose their actual child fields, and relation fields return Craft queries.

Declare the runtime contract with `FieldValueType::string()`, `boolean()`, `object(MyValue::class)`, `array()` or `relationQuery(MyElement::class)`. Use `none()` for cosmetic fields. Every declaration accepts `null` as the universal absent value; the type describes the non-null result of normalization, while requiredness remains a validation concern. Number and Calculations declare `string()` and expose `ReferenceType::Number` in their reference definitions. Their invalid text remains available to the validator. A type mismatch names the field, actual type and expected declaration.

Numeric GraphQL fields use `verbb\formie\gql\types\Decimal::getType()` to preserve the decimal-string runtime contract. Craft's `Number` scalar converts through PHP floats; use `FormieDecimal` for exact literals and string variables.

For a string field, the following methods belong inside your Field subclass:

```php
protected function defineValueType(): \verbb\formie\fields\definitions\FieldValueType
{
    return \verbb\formie\fields\definitions\FieldValueType::string();
}

public function normalizeValue(mixed $value, ?\craft\base\ElementInterface $element): mixed
{
    return trim((string)($value ?? ''));
}
```

A structured value object should expose read-only properties and explicitly declare which property paths references may access. Construct it once during normalisation. Keep field presentation settings, submission objects and service lookups outside the value. Repeated normalisation and every projection must leave the original unchanged.

Implement `verbb\formie\fields\values\FieldValueInterface` directly, or extend `BaseFieldValue` when an intrinsic `toArray()` representation is useful for domain parts. The interface deliberately contains only `Stringable`, `isEmpty()`, `canResolvePath()` and `getPathValue()`; browser, storage and public-data conversion belong to the owning field rather than the value object. The legacy `verbb\formie\base\FieldValueInterface` remains an empty Formie 3 compatibility marker, not the Formie 4 authoring contract.

Use the method intended for each operation:

| Method | Purpose |
|---|---|
| `normalizeValueFromRequest()` | Adapt browser and multipart input, then normalise; never decrypt or discard malformed input before validation |
| `normalizeValue()` | Produce the declared PHP value; passing an already normalised value must not change it |
| `defineValueForDb()` | Return a deliberate, lossless scalar/array storage representation |
| `serializeValueForDb()` | Final persistence entry point; applies whole-value encryption after the owning storage hook |
| `defineValueAsData()` | Return JSON-safe data for templates and application code |
| `serializeValueForClientInput()` | Return the browser input shape, which may differ from natural data |
| `defineValueForCondition()` | Return comparable values without using database serialisation |

Formie decodes trusted stored values before ordinary normalisation. Do not call storage decoding from request handlers. The base serializer rejects arbitrary objects, including objects implementing Arrayable or Serializable. A rich field must deliberately turn its domain parts into storage data. Nested storage uses child instance UIDs, and nested public data composes each contextual child's data projection.

String, data, export, integration, reference, reference block and summary projections each dispatch their own event. Reuse a protected projection implementation when appropriate; calling another public projection from your hook would also fire that projection's event. `EVENT_MODIFY_VALUE_AS_DATA` listeners receive a JSON-safe result and must return JSON-safe data.

For a form containing a Name field with handle `contactName`, these Twig calls serve different purposes:

```twig
{% set name = submission.getFieldValue('contactName') %}
<p>{{ submission.getFieldValueAsString('contactName') }}</p>
{% set parts = submission.getFieldValueAsData('contactName') %}
```

`name` is the immutable Name value in both UI modes. `parts` contains `name`, `prefix`, `prefixOption`, `firstName`, `middleName` and `lastName`; the single-name input uses `name`. Option data contains selected value, label and validity metadata without the available-option catalogue. Relation data is a list of selected `id`, `title` and `url` records; File Upload also includes `filename`. The Link adapter returns an immutable `CustomLinkFieldValue` with URL, label and submitted attributes; native Craft input rendering receives a separate reconstructed object. Use the field's integration, export or reference methods when those consumers require a different shape.

## Custom Field Adapters

::: tip
For a walkthrough, see [Bringing your Craft field into Formie (Custom Field adapters)](/guides/fields/bringing-your-craft-field-into-formie-custom-field-adapters).
:::

The built-in **Custom Field** field type lets Formie expose supported Craft fields without adding a separate Formie field type for every provider. Adapters are opt-in because Formie needs more than a Craft field class name to submit reliably.

Each adapter is responsible for:

- declaring the Craft field classes it supports,
- defining adapter-specific form-builder settings,
- rendering server-rendered and control panel submission inputs,
- normalizing and serializing submitted values,
- returning string, natural data, browser input, reference, summary, export and integration values,
- declaring GraphQL content and mutation shapes,
- declaring client-rendered input metadata.

Adapters declare `valueType($field)` and implement `serializeValueForClientInput()` independently of `getValueAsData()`. Use `decodeValueFromStorage()` for trusted legacy shapes; it is never called for request input. The default browser method deliberately shares the adapter data shape; override it when browser inputs require a different representation.

Built-in adapters include:

- **Link** — URL, email, phone and SMS link values using Craft’s Link field concepts, available without an extra plugin. Element link types are intentionally omitted until a public element-picker adapter exists.
- **Address (Google Maps)** — structured map values when a supported Google Maps Craft field class is installed.
- **Maps** — structured map values when a supported Maps/SimpleMap Craft field class is installed.

### Register an Adapter

Register adapters with `CustomFields::EVENT_REGISTER_CUSTOM_FIELD_ADAPTERS`:

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

### Value Storage

Custom Field uses JSON storage so adapters can support scalar and structured values through one Formie field type. Scalar adapters can return strings; structured adapters can return arrays or value objects and decide how those values appear in references, exports and integrations.

The adapter is selected when the field is created and is not editable afterward. Treat the adapter class stored in `customFieldAdapter` as part of the field’s storage contract.

The selected adapter’s builder settings are stored in `customFieldAdapterSettings`. The adapter owns the meaning of those settings, including defaults used by `getDefaultValue()`, server-rendered markup and client input metadata.

When an adapter supports a structured value, implement these methods together:

- `normalizeValue()`
- `serializeValue()`
- `isValueEmpty()`
- `getValueAsString()`
- `getValueAsData()`
- `getContentGqlType()`
- `getContentGqlMutationArgumentType()`

This keeps server-rendered submissions, GraphQL submissions, email summaries, exports and integrations aligned.

## Settings Schema
Custom field settings are defined with schema, not Twig templates. The schema tells the form builder which inputs to show, which setting each input saves to, and how the UI should be grouped.

For custom fields, each method maps to a tab in the field edit modal. Omit a method, or return an empty array, to hide that tab.

::: reference
### `defineFormBuilderGeneralSchema()`

**Returns:** `array`

Define the schema for the `General` tab for field settings.
:::

::: reference
### `defineFormBuilderSettingsSchema()`

**Returns:** `array`

Define the schema for the `Settings` tab for field settings.
:::

::: reference
### `defineFormBuilderAppearanceSchema()`

**Returns:** `array`

Define the schema for the `Appearance` tab for field settings.
:::

::: reference
### `defineFormBuilderAdvancedSchema()`

**Returns:** `array`

Define the schema for the `Advanced` tab for field settings.
:::

::: reference
### `defineFormBuilderConditionsSchema()`

**Returns:** `array`

Define the schema for the `Conditions` tab for field settings.
:::


This is enough for a common field with a label, placeholder, default value and standard appearance/settings controls:

```php
public function defineFormBuilderGeneralSchema(): array
{
    return [
        SchemaHelper::labelField(),
        SchemaHelper::textField([
            'label' => Craft::t('formie', 'Placeholder'),
            'instructions' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
            'name' => 'placeholder',
        ]),
        SchemaHelper::variableTextField([
            'label' => Craft::t('formie', 'Default Value'),
            'instructions' => Craft::t('formie', 'Set a default value for the field when it doesn’t have a value.'),
            'name' => 'defaultValue',
        ]),
    ];
}
```

The schema system is shared by fields, notifications, integrations and other form-builder UIs, so it is covered in more detail in [Schema](/developers/schema).

To expose organisation-wide defaults for your field type, opt in with `supportedDefaults()`. See [Field Defaults](/developers/field-defaults).

## Templates
There are also a number of rendering pieces custom fields should provide. These are namely:

- Twig template for when shown as a reference block.
- Twig template for server-rendered forms.
- Schema for the preview of the field in the form builder.
- Value methods for exports, summaries and submission previews when the field stores a non-simple value.

These are defined in functions, which are respectively:

- `getInputTemplatePath()`
- `getReferenceBlockTemplatePath()`
- `defineFormBuilderPreviewSchema()`
- `getInputTemplateVariables()`
- `defineValueAsString()`, `defineValueAsData()`, `defineValueForReference()`, `defineValueForReferenceBlock()`, `defineValueForExport()` or `defineValueForSummary()` when the default value handling is not enough.

```php
public static function getInputTemplatePath(): string
{
    return 'my-module/my-field/input';
}

public static function getReferenceBlockTemplatePath(): string
{
    return 'my-module/my-field/email';
}

public function defineFormBuilderPreviewSchema(): array
{
    return [
        SchemaHelper::previewInput(),
    ];
}

public function getInputTemplateVariables(Form $form, mixed $value): array
{
    return array_merge(parent::getInputTemplateVariables($form, $value), [
        'placeholder' => $this->placeholder,
    ]);
}
```

`getInputTemplatePath()` and `getReferenceBlockTemplatePath()` return template paths rather than HTML. Formie will determine where to look for these templates, either in its own default templates or in the folder where custom templates are defined. In most cases, defining the template paths is enough.

For value handling, override the protected `defineValue*()` methods, not the public `getValue*()` methods. The public methods wrap the `defineValue*()` methods and trigger Formie’s value events.

Use **reference** methods for singular value contexts, and **reference block** methods for richer looped output. Notification bodies are the main consumer of reference blocks today, but the concept is broader than “email HTML”.

Avoid overriding `renderInput()` or `getReferenceBlockHtml()` unless your field cannot be handled with templates and template variables. Keeping rendering in templates makes the field easier to override with Form Templates and Theme Config.

### Theme Config
You may opt to add support for [Theme Config](/theming/theme-config) in your custom field. To do this, keep the main HTML tags and attributes in your field class, and output them with `fieldtag()` in your Twig templates. This allows the tag to be changed by Theme Config.

For example, you could have the following in your Twig template:

```twig
{{ fieldtag('fieldInput', {
    value: value ?? false,
}) }}
```

Where you could define your attributes and tag in your field class:

```php
use verbb\formie\models\SlotTag;
use verbb\formie\theme\context\RenderContext;

protected function defineSlotTag(string $key, RenderContext $context): ?SlotTag
{
    $form = $context->form;
    $errors = $context->errors;

    $id = $this->getHtmlId($form);
    $dataId = $this->getHtmlDataId($form);

    if ($key === 'fieldInput') {
        $value = $context->get('value');

        return SlotTag::make('input')
            ->core([
                'type' => 'text',
                'id' => $id,
                'name' => $this->getHtmlName(),
                'value' => $value ?? false,
                'placeholder' => $this->placeholder ?: null,
                'required' => $this->required ? true : null,
                'data-formie-input' => true,
                'data-formie-input-id' => $dataId,
                'data-formie-input-type' => 'text',
                'data-formie-input-error-state' => $errors ? true : false,
                'data-formie-required-message' => $this->errorMessage ?: null,
                'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
            ])
            ->theme([
                'class' => [
                    'formie-input',
                    $errors ? 'formie-input-error' : false,
                ],
            ])
            ->instanceAttributes($this->getInputAttributes());
    }

    return parent::defineSlotTag($key, $context);
}
```

## Definition and Instance Identity

A `Field` is the full runtime object for one form field. Its `id`, `uid` and stable `reference` identify that instance. Its `definitionId` and `definitionUid` identify the shared field definition. Synced Fields use the same definition while retaining their own top-level instance identities. Label, handle, instructions, placeholder, options and defaults belong to the definition; `required` belongs to the instance.

Use instance identity when working with submission content, client field IDs or errors. Keep the established handle-based native input names and value accessors; do not substitute a definition ID. For example, a saved field's `getCpEditConfig()['id']` identifies the form field, while `$field->definitionId` is suitable for finding its shared metadata. `Fields::getFieldDefinitionById()` returns an immutable internal `FieldDefinition`, without runtime rendering or save methods. `Fields::getAllFields()` continues to return fully hydrated runtime fields.

Definition configuration is returned by `getDefinitionSettings()`. Override `getInstanceSettings()` and `applyInstanceSettings()` only when a field has additional per-form-instance configuration that must not be shared with other instances of a Synced Field. Call the parent methods so the built-in `required` setting remains part of the instance contract.

Registration requires a concrete subclass of `Field`. `FieldInterface` is available for type hints; implementing that interface independently does not register a valid field. Declare metadata with `defineFieldType()` and the ordinary static methods. The base general-settings and preview schemas are valid defaults. Metadata requests use fresh field prototypes, so extensions must not rely on mutations to a previously returned prototype.

`ParentFieldInterface` identifies a nested container. `ChildFieldInterface` identifies an intrinsic child, such as `firstName`, with a fixed parent/type/handle relationship. A Repeater's arbitrary nested fields are ordinary field instances, not intrinsic children. Use `FixedParentField` for intrinsic parts and `RepeatableParentField` for repeated arbitrary fields.

Use `getFields()` for immediate fields, `getEnabledFields()` to omit disabled fields and `getFieldsRecursively()` for the complete nested graph. Pages and layouts flatten their immediate rows; recursive traversal descends into parent fields. Repeater callers can pass a row key to `getFields($rowKey)` to receive fields with that row's input context.

Field settings returned to the builder are editable data, not permission to replace the field's identity. Builder creation accepts registered types and declared setting attributes. Existing instances must belong to the form, and Synced Fields selections use a server-issued selection token. Do not construct request-supplied component classes or use posted definition IDs as authority.
