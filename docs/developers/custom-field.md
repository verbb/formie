# Custom Field
You can add your own custom fields to be compatible with Formie by using the provided events.

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

Fields should extend `verbb\formie\base\Field`. This gives your field the form-builder schema, front-end rendering, value handling, optional client-rendered configuration, and reference / reference-block rendering behaviour Formie expects.

For fields that contain other fields, extend the parent field class that matches the behaviour you need:

- `ParentField` for editable nested fields.
- `FixedParentField` for fields with a fixed set of child fields, such as Name, Address or Date/Time.
- `RepeatableParentField` for fields that repeat a group of nested fields.

Formie fields are Formie components, not Craft custom fields. Some concepts will feel familiar if you have built Craft fields before, but use Formie’s field base classes and methods rather than Craft’s `Field` class. Refer to the [Field](/reference/field) object documentation for the data available on a field instance.

::: tip
There is also a [step-by-step custom field guide](https://verbb.io/craft-plugins/formie/user-guides/creating-your-own-custom-field-from-scratch-formie-3) that walks through building a field from scratch.
:::

## Methods

Method | Description
--- | ---
`displayName()` | Returns the name to be used for the field.
`getInputTemplatePath()` | Returns the path to the front-end template for this field.
`getReferenceBlockTemplatePath()` | Returns the path to the template used when the field is rendered as a reference block.
`getSvgIconPath()` | Returns the path to the SVG icon used as the field type in the control panel.
`defineFormBuilderPreviewSchema()` | Returns the preview schema shown in the form builder.
`defineFormBuilderGeneralSchema()` | Defines the schema for the `General` tab in the field edit modal.
`defineFormBuilderSettingsSchema()` | Defines the schema for the `Settings` tab in the field edit modal.
`defineFormBuilderAppearanceSchema()` | Defines the schema for the `Appearance` tab in the field edit modal.
`defineFormBuilderAdvancedSchema()` | Defines the schema for the `Advanced` tab in the field edit modal.
`defineFormBuilderConditionsSchema()` | Defines the schema for the `Conditions` tab in the field edit modal.
`supportedDefaults()` | Returns setting handles that can be configured as organisation-wide defaults in **Settings → Defaults**. See [Field Defaults](/developers/field-defaults).
`defineValidationRules()` | Defines the validation rules sent to Formie’s front-end assets.
`defineRules()` | Defines server-side Yii validation rules for the field model.
`defineClientInput()` | Adds input-specific configuration to the client-rendered field definition.
`clientDefinition()` | Declares the base field type and input metadata used in the structured client payload.
`clientChildren()` | Declares whether the client payload is scalar, part-based, or row-based for nested fields.
`defineClientModules()` | Registers optional client modules the field needs in client-rendered flows.
`clientModules()` | Returns the normalized client-module manifest entries for browser-managed field behaviour.
`references()` | Declares the field’s reference selectors and nested-reference rules for tokens and picker UIs.
`variableSources()` | Returns the variable-picker sources exposed by the field.
`conditions()` | Returns normalized field-condition metadata for browser-managed flows.
`defineFieldSlotTag()` | Defines the HTML tag and attributes used by `fieldtag()` slots in the field’s Twig template.
`getInputTemplateVariables()` | Adds variables available to the front-end field template.
`defineValueAsString()` | Defines the field value when Formie needs a string value.
`defineValueAsArray()` | Defines the field value when Formie needs an array value.
`defineValueForExport()` | Defines the field value used in exports.
`defineValueForSummary()` | Defines the field value used in summaries.
`defineValueForReference()` | Defines the field value used for singular reference contexts.
`defineValueForReferenceBlock()` | Defines the field value used before rendering reference-block content.
`defineValueForIntegration()` | Defines the field value used when sending data to integrations.
`fieldKind()` | Defines the kind of field for the client input contract.
`valueClass()` | Declares value capabilities and client-payload serialization. It does not control the normal PHP/Twig submission value shape on its own.

Refer to the [Field](/reference/field) object documentation for more.

## Settings Schema
Custom field settings are defined with schema, not Twig templates. The schema tells the form builder which inputs to show, which setting each input saves to, and how the UI should be grouped.

For custom fields, each method maps to a tab in the field edit modal. Omit a method, or return an empty array, to hide that tab.

Method | Description
--- | ---
`defineFormBuilderGeneralSchema()` | Define the schema for the `General` tab for field settings.
`defineFormBuilderSettingsSchema()` | Define the schema for the `Settings` tab for field settings.
`defineFormBuilderAppearanceSchema()` | Define the schema for the `Appearance` tab for field settings.
`defineFormBuilderAdvancedSchema()` | Define the schema for the `Advanced` tab for field settings.
`defineFormBuilderConditionsSchema()` | Define the schema for the `Conditions` tab for field settings.

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
- Twig template for the front-end.
- Schema for the preview of the field in the form builder.
- Value methods for exports, summaries and submission previews when the field stores a non-simple value.

These are defined in functions, which are respectively:

- `getInputTemplatePath()`
- `getReferenceBlockTemplatePath()`
- `defineFormBuilderPreviewSchema()`
- `getInputTemplateVariables()`
- `defineValueAsString()`, `defineValueAsArray()`, `defineValueForReference()`, `defineValueForReferenceBlock()`, `defineValueForExport()` or `defineValueForSummary()` when the default value handling is not enough.

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

protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
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
                'placeholder' => Craft::t('formie', $this->placeholder) ?: null,
                'required' => $this->required ? true : null,
                'data-formie-input' => true,
                'data-formie-input-id' => $dataId,
                'data-formie-input-type' => 'text',
                'data-formie-input-error-state' => $errors ? true : false,
                'data-formie-required-message' => Craft::t('formie', $this->errorMessage) ?: null,
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

    return parent::defineFieldSlotTag($key, $context);
}
```
