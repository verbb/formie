# Schema

::: tip
For the Craft macros analogy, field and integration patterns, and debugging schema issues, see [Everything you need to know about Formie schemas](/guides/developers/everything-you-need-to-know-about-formie-schemas).
:::

Formie uses PHP-defined schema to render many parts of the form builder, including field settings, notification settings, page button settings and integration form settings. A schema is an array of nodes that describe the UI the builder should render and the settings those inputs should save.

Most schemas are built with `SchemaHelper` methods. You can also write raw schema nodes when you need a field or layout the helper does not cover.

```php
use Craft;
use verbb\formie\helpers\SchemaHelper;

return [
    SchemaHelper::textField([
        'label' => Craft::t('formie', 'Placeholder'),
        'instructions' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
        'name' => 'placeholder',
    ]),
    SchemaHelper::lightswitchField([
        'label' => Craft::t('formie', 'Required Field'),
        'instructions' => Craft::t('formie', 'Whether this field should be required when filling out the form.'),
        'name' => 'required',
    ]),
];
```

## Schema Nodes
A field node uses `$field` to choose the input type, and `name` to choose the setting key that will be saved. Common attributes include `label`, `instructions`, `required`, `validation`, `options`, `placeholder` and `if`.

```php
[
    '$field' => 'select',
    'label' => Craft::t('formie', 'Display Type'),
    'instructions' => Craft::t('formie', 'Choose how the field should be displayed.'),
    'name' => 'displayType',
    'options' => [
        ['label' => Craft::t('formie', 'Dropdown'), 'value' => 'dropdown'],
        ['label' => Craft::t('formie', 'Radio Buttons'), 'value' => 'radio'],
    ],
]
```

The schema is rendered by the form builder UI, so these nodes are not Twig templates. Treat the array as the source of truth for the editor experience.

## Helpers
`SchemaHelper` keeps common nodes shorter and gives Formie a single place to apply its own defaults. Prefer helpers for common field types and reusable Formie settings.

::: reference
### `textField()`

**Use:** Text input.

Text input.
:::

::: reference
### `textareaField()`

**Use:** Textarea input.

Textarea input.
:::

::: reference
### `selectField()`

**Use:** Select input.

Select input.
:::

::: reference
### `comboboxField()`

**Use:** Combobox input.

Combobox input.
:::

::: reference
### `numberField()`

**Use:** Number input.

Number input.
:::

::: reference
### `dateField()`

**Use:** Date input.

Date input.
:::

::: reference
### `checkboxSelectField()`

**Use:** Checkbox group.

Checkbox group.
:::

::: reference
### `checkboxField()`

**Use:** Checkbox input.

Checkbox input.
:::

::: reference
### `lightswitchField()`

**Use:** Lightswitch input.

Lightswitch input.
:::

::: reference
### `colorField()`

**Use:** Colour input.

Colour input.
:::

::: reference
### `tableField()`

**Use:** Editable table input.

Editable table input.
:::

::: reference
### `staticTableField()`

**Use:** Static table input.

Static table input.
:::

::: reference
### `variableTextField()`

**Use:** Text input with variable-picker support.

Text input with variable-picker support.
:::

::: reference
### `richTextField()`

**Use:** Rich text input.

Rich text input.
:::

::: reference
### `htmlEditorField()`

**Use:** Syntax-highlighted HTML/code input.

Syntax-highlighted HTML/code input.
:::

::: reference
### `calculationsField()`

**Use:** Calculation editor.

Calculation editor.
:::

::: reference
### `elementSelectField()`

**Use:** Element select input.

Element select input.
:::

::: reference
### `fieldSelectField()`

**Use:** Formie field select input.

Formie field select input.
:::

::: reference
### `groupField()`

**Use:** Grouped schema fields.

Grouped schema fields.
:::

::: reference
### `fieldWrap()`

**Use:** Shared label/instructions around multiple smaller fields.

Shared label/instructions around multiple smaller fields.
:::


Reusable Formie field-setting helpers include:

::: reference
### `labelField()`

**Use:** Field label.

Field label.
:::

::: reference
### `handleField()`

**Use:** Field handle.

Field handle.
:::

::: reference
### `labelPosition()`

**Use:** Label position.

Label position.
:::

::: reference
### `subFieldLabelPosition()`

**Use:** Sub-field label position.

Sub-field label position.
:::

::: reference
### `instructions()`

**Use:** Field instructions.

Field instructions.
:::

::: reference
### `instructionsPosition()`

**Use:** Instructions position.

Instructions position.
:::

::: reference
### `cssClasses()`

**Use:** CSS classes.

CSS classes.
:::

::: reference
### `containerAttributesField()`

**Use:** Container attributes.

Container attributes.
:::

::: reference
### `inputAttributesField()`

**Use:** Input attributes.

Input attributes.
:::

::: reference
### `prePopulate()`

**Use:** Prefill query parameter setting.

Prefill query parameter setting.
:::

::: reference
### `enableConditionsField()`

**Use:** Enable conditions setting.

Enable conditions setting.
:::

::: reference
### `conditionsField()`

**Use:** Conditions builder.

Conditions builder.
:::

::: reference
### `enableContentEncryptionField()`

**Use:** Content encryption setting.

Content encryption setting.
:::

::: reference
### `includeInEmailFieldSummariesField()`

**Use:** Include in email field summaries setting.

Include in email field summaries setting.
:::

::: reference
### `emailFieldSummaryValue()`

**Use:** Email field summary value setting.

Email field summary value setting.
:::

::: reference
### `visibility()`

**Use:** Visibility setting.

Visibility setting.
:::

::: reference
### `matchField()`

**Use:** Match another field.

Match another field.
:::


## Layout and HTML
Use `$el` for plain HTML elements and `children` to nest schema nodes. This is helpful when you need grouping, extra explanatory content or a small layout wrapper around multiple inputs.

```php
[
    '$el' => 'div',
    'attrs' => [
        'class' => 'some-wrapper',
    ],
    'children' => [
        [
            '$el' => 'p',
            'children' => Craft::t('formie', 'These settings control how the value is limited.'),
        ],
        SchemaHelper::numberField([
            'label' => Craft::t('formie', 'Maximum'),
            'name' => 'max',
        ]),
    ],
]
```

Use `$cmp` when you need to render a registered form-builder component rather than a plain HTML element. Formie registers several builder-specific components and fields on top of the shared [SchemaForm](https://verbb.io/plugin-kit/forms/overview) system, including field builders, notification editors, integration settings, preview components and Formie-specific field inputs.

## Conditions
Use `if` to show a schema node only when a condition is met. The simplest form checks another value in the same schema scope.

```php
SchemaHelper::textField([
    'label' => Craft::t('formie', 'Error Message'),
    'instructions' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
    'name' => 'errorMessage',
    'if' => 'required',
])
```

For nested schemas, Formie tracks child prefixes so validation and error state still resolve against the correct setting path. Helpers like `tableField()`, `groupField()` and Formie’s nested layout fields handle the common cases for you.

## Tables
Tables are useful for settings with multiple rows, such as dropdown options or table columns.

```php
SchemaHelper::tableField([
    'label' => Craft::t('formie', 'Options'),
    'instructions' => Craft::t('formie', 'Define the available options for users to select from.'),
    'name' => 'options',
    'enableBulkOptions' => true,
    'newRowDefaults' => [
        'default' => false,
    ],
    'columns' => [
        [
            'type' => 'text',
            'name' => 'label',
            'label' => Craft::t('formie', 'Option Label'),
            'required' => true,
        ],
        [
            'type' => 'value',
            'name' => 'value',
            'label' => Craft::t('formie', 'Value'),
            'source' => 'label',
        ],
        [
            'type' => 'radio',
            'name' => 'default',
            'label' => Craft::t('formie', 'Default'),
            'allowUnselect' => true,
        ],
    ],
])
```

Common table column types include `text`, `value`, `handle`, `checkbox`, `radio` and `select`.

## Preview Schema
Field previews use a smaller preview schema rendered by the form builder preview layer. Use `defineFormBuilderPreviewSchema()` on a field and return preview helpers where possible.

```php
public function defineFormBuilderPreviewSchema(): array
{
    return [
        SchemaHelper::previewInput(),
    ];
}
```

Preview helpers include `previewInput()`, `previewTextarea()`, `previewSelect()`, `previewChoiceList()`, `previewContainerParent()`, `previewElementField()`, `previewPhone()`, `previewPayment()`, `previewTable()`, `previewMessage()`, `previewRichText()`, `previewHtml()`, `previewHeading()`, `previewGroup()`, `previewSection()`, `previewSignature()`, `previewSummary()`, `previewAgree()` and `previewRecipients()`.

Define field previews with preview schema so the builder can render their structure.
