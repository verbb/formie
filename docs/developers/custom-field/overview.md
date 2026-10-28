# Overview

Create a custom Formie field type when you need your own field behaviour, settings, and rendering. Register the field with Formie, then define its form-builder schema, value handling, and templates.

To expose an existing Craft field through Formie’s built-in **Custom Field** field type, see [Field Adapters](/developers/custom-field/adapters).

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

Formie fields are Formie components, not Craft custom fields. Some concepts will feel familiar if you have built Craft fields before, but use Formie’s field base classes and methods rather than Craft’s `Field` class. Refer to the [Field](/reference/field) object documentation for the data available on a field instance.

::: tip
There is also a [step-by-step guide for creating a Formie field type](/guides/fields/creating-a-formie-field-type-from-scratch) that walks through building a field from scratch.
:::

### Nested Fields

For fields that contain other fields, extend the parent field class that matches the behaviour you need:

- `ContainerParentField` for an editable group of nested fields. `ParentField` supplies shared behaviour for the parent classes listed here.
- `FixedParentField` for fields with a fixed set of child fields, such as Name, Address or Date/Time.
- `RepeatableParentField` for fields that repeat a group of nested fields.

`ParentFieldInterface` identifies a field that contains other fields. Fixed parts, such as a Name field’s `firstName`, implement `ChildFieldInterface`. Fields added by an author inside a Group or Repeater are ordinary field instances.

Use `getFields()` to read a parent’s immediate fields, `getEnabledFields()` to omit disabled fields, or `getFieldsRecursively()` to include all nested levels. For a Repeater, `getFields($rowKey)` returns fields with their input context set to that row.

## Methods

Use public getters to consume a field's completed configuration or projected value. Author configuration through the named hooks: `defineFormBuilder*Schema()` methods are public; value, browser-module, client-input and reference declaration hooks are protected. Public value getters dispatch their corresponding events, so a projection hook should reuse protected implementations instead of calling another public projection getter. To reuse the data projection, call protected `resolveValueAsData($value, $element = null)`: it resolves `defineValueAsData()` and supported legacy overrides without firing the data event. Keep dedicated condition, browser and integration projections when their shape differs from the data representation.

Use `MyField::class` for the fully qualified class identity and `MyField::getShortClassName()` for its short class name. `fieldKind()` describes an input kind, `valueType()` describes the normalized PHP value, and `getPresentationDisplayType()` identifies a presentation mode on fields that support presentation delegates. These represent different concepts.

Overriding `getShortClassName()` also changes the derived `kebabClassName()` and `lowerClassName()` results, the default Theme Config key and any default template paths derived from its name. For example, returning `SharedWidget` produces `fields/shared-widget` as the default input and reference-block template path. Explicit template-path overrides and fixed native template defaults continue to determine their own paths. Use the template-path methods when you only want to choose different templates.

Fields that extend a native field inherit its supported settings and option-source capabilities. For example, a Dropdown subclass can use integration-backed options; a Recipients subclass still requires a source that explicitly supports recipients. Constructors and `setAttributes()` accept field configuration; normal settings are emitted by `getSettings()`. `parentField` and `namespace` are setter-backed runtime properties and are preserved when a field delegates presentation to another field. Keep these runtime properties separate from stored settings.


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
### `defineFormBuilderValidationSchema()`

**Returns:** `array`

Defines the schema for the `Validation` tab in the field edit modal. Like the other `defineFormBuilder*Schema()` hooks, this method is public.
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

Returns setting handles that can be configured as organisation-wide defaults in **Settings → Form Builder Defaults**. See [Field Defaults](/developers/custom-field/field-defaults).
:::

::: reference
### `defineBrowserValidationRules()`

Defines the rules sent to Formie’s browser assets. Pair them with submission rules and browser registration as shown in [Validation](/developers/validation#submission-rules-and-browser-definitions).
:::

::: reference
### `getElementValidationRules()`

Defines validators for the visitor’s submitted field value. Keep the parent rules and pair your custom rule with browser feedback; [Validation](/developers/validation) explains the PHP and JavaScript APIs.
:::

::: reference
### `defineRules()`

Defines Yii validation rules for the field model’s settings. For submitted answers, use `getElementValidationRules()`; see [Validation](/developers/validation#submission-rules-and-browser-definitions).
:::

::: reference
### `defineClientRenderedInput()`

Adds input-specific configuration to the client-rendered field definition.
:::

::: reference
### `getClientRenderedDefinition()`

**Returns:** `array`

Returns the complete client-rendered field definition, including input configuration, nested structure, conditions and validation. Override `defineClientRenderedType()` and `defineClientRenderedInput()` to declare the field type and input configuration.
:::

::: reference
### `clientRenderedChildren()`

**Returns:** `verbb\formie\fields\definitions\FieldClientRenderedChildren`

Returns the nested structure declared by the protected `defineClientRenderedChildren()` hook. Override that hook to describe scalar, part-based or row-based input; use `clientRenderedChildren()` when reading the completed declaration.
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

Declares the field values available to reference tokens, field selectors and the Variable Picker. Use `FieldReferenceValue::primary()` for the field’s primary reference value and `FieldReferenceValue::selector()` for deliberate projections such as an Address field’s city. Each declaration provides semantic types, inline or block shape, authoring availability and any structured setting condition; the same declaration is enforced when the token resolves.
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

Defines the JSON-safe value. Return useful scalars for primitive fields and explicit named data for structured fields.
:::

::: reference
### `defineValueForExport()`

Defines the field value used in exports.
:::

::: reference
### `defineValueForReport()`

Defines the value shown in a single report cell, including CSV, Excel and other report exports. It defaults to `defineValueAsString()`. Override `protected function defineValueForReport(mixed $value, ?ElementInterface $element = null, bool $useOptionLabels = true): mixed` to customize report output without changing the field's stored value or structured export data.

Return a plain-text string, another scalar, or `null` for an empty cell. Honor `$useOptionLabels` if your field offers options. Container fields should call each child's `getValueForReport()` with the same preference so child formatting and masking are preserved.
:::

### Report Subcolumns

A parent field can implement `verbb\formie\base\ReportSubfieldsInterface` to offer separate report columns. Return the direct child field objects from `getReportSubfields()` in the desired layout order. These must belong to the field's normal child layout so submission values can be resolved through their handles. Disabled and cosmetic children are excluded automatically. Return an empty array when the field has no report subcolumns.

```php
use verbb\formie\base\ReportSubfieldsInterface;
use verbb\formie\fields\Group;

class Details extends Group implements ReportSubfieldsInterface
{
    public function getReportSubfields(): array
    {
        return $this->getFields();
    }
}
```

Reports expose each declared child as `parentHandle.childHandle`, labelled with both field labels. Child cells use the child's report projection, including option-label preferences. The parent keeps its combined report projection. **Parent Field Columns** on the report's **Display** tab controls automatic inclusion; manually selected reports can choose either representation. Parent fields do not opt in automatically, and repeating rows are not implicitly flattened into columns.

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

## Options and Presentation

`options()` returns authored option rows, `getResolvedOptions()` resolves the current source, and `getFieldOptions()` returns the rows suitable for display, including availability and any recipient tokenization. These methods are not interchangeable. `isOptionHidden()` and `isOptionDisabled()` interpret canonical option availability.

Element fields use `optionLimit` to limit results fetched from their source. Choice fields use the boolean `limitOptions` with `min` and `max` to validate selection counts. Quiz uses `displayType` to select its presentation, with `DISPLAY_RADIO`, `DISPLAY_DROPDOWN` and `DISPLAY_CHECKBOXES` constants. Recipients derives selection cardinality from `displayType`; it has no independent editable multiplicity setting.

Phone and Address use `allowedCountryCodes` for configured country codes. File Upload uses `minFileSize` and `maxFileSize` in decimal megabytes (1 MB = 1,000,000 bytes), and `maxFiles` for the number of files. Repeater and Table use `addRowLabel` for their add-row button text.

A normalized `RecipientsFieldValue` exposes read-only `value`, `label`, `valid` and `multiple` properties, plus `all()`, `values()` and `labels()` for selections. It contains selection data, not a widget type. The field owns presentation, storage and browser tokenization; pass a normalized value to `serializeValueForClientInput()` when preparing browser input.

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
| `serializeValueForClientInput()` | Return the browser input shape, which may differ from the data representation |
| `defineValueForCondition()` | Return comparable values without using database serialisation |

Formie decodes trusted stored values before ordinary normalisation. Do not call storage decoding from request handlers. The base serializer rejects arbitrary objects, including objects implementing Arrayable or Serializable. A rich field must deliberately turn its domain parts into storage data. Nested storage uses child instance UIDs, and nested public data composes each contextual child's data projection.

String, data, export, integration, reference, reference block and summary projections each dispatch their own event. Reuse a protected projection implementation when appropriate; calling another public projection from your hook would also fire that projection's event. `EVENT_MODIFY_VALUE_AS_DATA` listeners receive a JSON-safe result and must return JSON-safe data.

For a form containing a Name field with handle `contactName`, these Twig calls serve different purposes:

```twig
{% set name = submission.getFieldValue('contactName') %}
<p>{{ submission.getFieldValueAsString('contactName') }}</p>
{% set parts = submission.getFieldValueAsData('contactName') %}
```

`name` is the immutable Name value in both UI modes. `parts` contains `name`, `prefix`, `prefixOption`, `firstName`, `middleName` and `lastName`; the single-name input uses `name`. Option data is the selected scalar, or a list of selected scalars for multiple selections. This also applies to Name Prefix and Address Country subfields: a prefix can return `dr` as data and `Doctor` as display/export text; a country configured to store codes returns `AU` as data rather than its display label. Labels, validity and selection helpers remain available on the normalized PHP value object. Empty single selections return `null`; empty multiple selections return `[]`. Relation data is a list of selected `id`, `title` and `url` records; File Upload also includes `filename`. The Link adapter returns an immutable `CustomLinkFieldValue` with URL, label and submitted attributes; native Craft input rendering receives a separate reconstructed object. Use the field's integration, export or reference methods when those consumers require a different shape.

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

The schema system is shared by fields, notifications, integrations and other form-builder UIs, so it is covered in more detail in [UI Schemas](/developers/ui-schemas).

To expose organisation-wide defaults for your field type, opt in with `supportedDefaults()`. See [Field Defaults](/developers/custom-field/field-defaults).

### Shared and Per-Form Settings

[Synced Fields](/forms/synced-fields) share a definition containing settings such as labels, placeholders, and options. Each placement of that field is a separate instance with its own `required` setting. For example, the same synced Email field can be required on a registration form and optional on a feedback form.

Custom fields inherit this behaviour. If your field needs additional settings that vary between placements, override `getInstanceSettings()` and `applyInstanceSettings()`, calling the parent methods to preserve the built-in `required` setting. `getDefinitionSettings()` returns the shared settings, excluding those declared by `getInstanceSettings()`.

When reading submission values, continue to use the field handle. For code that needs to identify a field or its shared definition, see the instance and definition properties in the [Field reference](/reference/field#id).

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
- `defineValueAsString()`, `defineValueAsData()`, `defineValueForReference()`, `defineValueForReferenceBlock()`, `defineValueForExport()`, `defineValueForReport()` or `defineValueForSummary()` when the default value handling is not enough.

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

For reference blocks, `getReferenceBlockOptions($submission, $notification, $value, $renderOptions = [])` supplies template variables. `hasReferenceBlockLabel()` controls whether the surrounding block shows the field label, and `hasReferenceBlockPlaceholder()` controls the empty-value placeholder. These public methods can be overridden on a native field subclass; call the corresponding parent method when extending its behaviour. A custom `getReferenceBlockHtml($submission, $notification, $value, $renderOptions = [])` can return HTML, or `false`/`null` to omit the field’s output.

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
