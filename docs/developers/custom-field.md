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

Fields should extend the `FormField` base class. Where it's not possible to extend a class (your class already extends another one), the `FormFieldTrait` should be used. In both cases, fields should adhere to the `FormFieldInterface` interface,

For more complex fields that have sub-fields, you should implement the `SubfieldInterface` interface and use the `SubfieldTrait` trait.

In both bases, our `FormField` class itself extends from Craft's [Field](docs:developers/field) class. This means any methods, attributes or functionality used by regular Craft fields, can be used for your custom Formie fields.

## Methods

Method | Description
--- | ---
`displayName()` | Returns the name to be used for the field.
`getFrontEndInputTemplatePath()` | Returns the path to the front-end template for this field. This path is relative to the path set in your [Form Template](docs:template-guide/form-templates), if you are using a custom template.
`getSvgIconPath()` | Returns the path to the SVG icon used as the field type in the control panel.
`getIsTextInput()` | Whether this is a text-based input or not.
`getFrontEndInputHtml()` | Returns the HTML for a the front-end template for a field.
`getPreviewInputHtml()` | Returns the HTML used in the form builder as a preview. Valid [Vue](https://vuejs.org/) templating can be used here.
`getFieldDefaults()` | Returns any default settings that are used when the field is created.

 Refer to the [Field](docs:developers/field) object documentation for more.

## Settings Schema
Each field will have a number of settings specific to that field. The form builder and field editor uses [Vue](https://vuejs.org/) to create an excellent user experience when editing all aspects of the form. However, this makes it difficult to manage the settings for your field, where in typical Craft you might use Twig.

To cater for this, we provide a number of methods for you to define a schema for your field settings. Each schema consists of a collection of objects that define what fields your settings have. This schema is then rendered in Vue components, without you having to worry about handling validation, or saving values.

The following methods are available. Each method matches to a specific tab in the field edit modal. Omitting the method or returning an empty array will mean the tab will not be shown altogether.

Method | Description
--- | ---
`defineGeneralSchema()` | Define the schema for the `General` tab for field settings.
`defineSettingsSchema()` | Define the schema for the `Settings` tab for field settings.
`defineAppearanceSchema()` | Define the schema for the `Appearancee` tab for field settings.
`defineAdvancedSchema()` | Define the schema for the `Advanced` tab for field settings.

For those interested in further reading, we use [Vue Formulate](https://vueformulate.com/) to manage the settings for fields. The below is largely applicable to the [Generating forms](https://vueformulate.com/guide/forms/generating-forms/) documentation.

### Creating your Schema
Let's look at an example schema. Here we want to return a text `<input>` to store the placeholder for the field.

```php
public function defineGeneralSchema(): array
{
    return [
        [
            'label' => Craft::t('formie', 'Placeholder'),
            'help' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
            'name' => 'placeholder',
            'type' => 'text',
            'class' => 'text fullwidth',
            'autocomplete' => 'off',
        ],
    ];
}
```

This tells Formie all the information it needs to render the field. In addition, you can also provide any valid attributes to be added to the form component element. For example, you might want to include some classes or data-attributes:

```php
[
    // ...
    'class' => 'some-class',
    'data-value' => 'some-value',
]
```

You can also include any arbitrary DOM elements, instead of form elements. Here, we wrap a text field in a `<div>` tag using the `children` attribute, and the `component`, and also include an `<img>` element. This gives you a lot of flexibility on presenting your field settings.

```php
[
    'component' => 'div',
    'class' => 'some-div',
    'children' => [
        [
            'component' => 'img',
            'src' => 'some/image/path/image.svg',
            'style => 'width: 50px;',
        ],
        [
            'label' => Craft::t('formie', 'Placeholder'),
            'help' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
            'name' => 'placeholder',
            'type' => 'text',
            'class' => 'text fullwidth',
            'autocomplete' => 'off',
        ],
    ]
],
```

As shown above, you can use `children` to infinitely nest elements.

Available attributes are:

Attribute | Description
--- | ---
`help` | Help/instruction text shown underneath the label.
`id` | The id of the input (defaults to an auto-generated one).
`label` | A label for the field.
`name` | The name attribute should match the settings property in your class.
`options` | For some fields, provide a set of options. See [Options](#options).
`placeholder` | Add a placeholder to the input.
`type` | Define the type for the field. See [Field Types](#field-types).
`validation` | Any validation required for the field. See [Validation](#validation).

Under the hood, we pass these values to a `<component :is>` dynamic Vue component. For further reading, see [Dynamic Components](https://vuejs.org/v2/guide/components.html#Dynamic-Components)

### Schema Helpers

You can also shorten the above using a number of our schema-helper functions, to take the boilerplate out of this code.

```php
public function defineGeneralSchema(): array
{
    return [
        SchemaHelper::textField([
            'label' => Craft::t('formie', 'Placeholder'),
            'help' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
            'name' => 'placeholder',
        ]),
    ];
}
```

There are also a number of pre-built fields such as Label or Handle, which almost every field uses, which are even shorter!

```php
return [
    SchemaHelper::labelField(),
    SchemaHelper::handleField(),
];
```

Available schema helpers are:

Method | Description
--- | ---
`textField()` | To output a `<input type="text">` element.
`textareaField()` | To output a `<textarea>` element.
`selectField()` | To output a `<select>` element.
`dateField()` | To output a `<date>` component, with support for time and date.
`checkboxSelectField()` | To output a collection of `<input type="checkbox">` elements.
`checkboxField()` | To output a `<input type="checkbox">` element.
`lightswitchField()` | To output a `<lightswitch>` component.
`toggleContainer()` | To output a `<toggle-container>` component for a group of hidden fields. You can also provide logic for show/hiding.
`toggleBlocks()` | To output a `<toggle-blocks>` Vue component. This should be used to wrap `toggleBlock()` with validation.
`toggleBlock()` | To output a `<toggle-block>` Vue component. A collapsible, and enable-able group of fields.
`tableField()` | To output a table field for defining multiple rows of content. See [Table](#table).
`variableTextField()` | To output a `<input type="text">` element, including a dropdown to pick variables from.
`richTextField()` | To output a WYSIWYG field using [TipTap](https://tiptap.scrumpy.io/).

Method | Description
--- | ---
`labelField()` | To output a field component for the label.
`handleField()` | To output a field component for the handle.
`labelPosition()` | To output a field component for the label position.
`subfieldLabelPosition()` | To output a field component for the subfield label position.
`instructions()` | To output a field component for the instructions.
`instructionsPosition()` | To output a field component for the instructions position.
`cssClasses()` | To output a field component for the CSS classes.
`containerAttributesField()` | To output a field component for the container attributes.
`inputAttributesField()` | To output a field component for the input attributes.

### Options
Some fields like a select or radio buttons can provide an `options` attribute.

```php
[
    'options' => [
        ['label' => 'Some Label', 'value' => 'some-value'],
    ]
]
```

### Table
A table field is almost visually identical to Craft's own Table field settings for a Dropdown, Radio and of course Table field. This field requires a `columns` value to define what columns you'd like to use in the table field. It should also define what the defaults are for a new row added, with `newRowDefaults`.

```php
[
    'newRowDefaults' => [
        'label' => '',
        'value' => '',
        'isOptgroup' => false,
        'isDefault' => false,
    ],
    'columns' => [
        [
            'type' => 'optgroup',
            'label' => Craft::t('formie', 'Optgroup?'),
            'class' => 'thin checkbox-cell',
        ],
        [
            'type' => 'label',
            'label' => Craft::t('formie', 'Option Label'),
            'class' => 'singleline-cell textual',
        ],
        [
            'type' => 'value',
            'label' => Craft::t('formie', 'Value'),
            'class' => 'code singleline-cell textual',
        ],
        [
            'type' => 'default',
            'label' => Craft::t('formie', 'Default?'),
            'class' => 'thin checkbox-cell',
        ],
    ],
]
```

Available column types are:

Method | Description
--- | ---
`optgroup` | A checkbox to show an optgroup.
`label` | A text field that is used for `value` or `handle` to generate its value.
`heading` | A text field that is used for `value` or `handle` to generate its value.
`value` | A text field that copied the value from `label` or `heading`.
`handle` | A text field that generates a valid handle based on the `label` or `heading`.
`default` | A checkbox to mark as default. Can be used in combination with `allowMultipleDefault`.
`width` | A field to define the width of the columns in rows.
`type` | A select field to select from a column field type.


### Field Types
Refer to the [Vue Formulate](https://vueformulate.com/guide/inputs/types/button/) docs on the available input types to select from.


### Validation
Refer to the [Vue Formulate](https://vueformulate.com/guide/validation/#available-rules) docs on the available validation rules.

```php
SchemaHelper::textField([
    'label' => Craft::t('formie', 'Placeholder'),
    'help' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
    'name' => 'placeholder',
    'validation' => 'required|starts_with:Some Value'
]),
```

## Templates
There are also a number of templates fields use. These are namely:

- Twig template for when shown in an email.
- Twig template for the front-end.
- Twig/Vue template the preview of the form in the form builder.
- Twig template for when viewing a submission.

These are defined in functions, which are respectively:

- `getPreviewInputHtml()`
- `getFrontEndInputTemplatePath()`
- `getInputHtml()`
- `getEmailTemplatePath()`
- `getEmailHtml()`

```php

public static function getFrontEndInputTemplatePath(): string
{
    return 'my-module/my-field';
}

public static function getEmailTemplatePath(): string
{
    return 'my-module/my-field';
}

public function getPreviewInputHtml(): string
{
    return Craft::$app->getView()->renderTemplate('my-module/my-field/preview', [
        'field' => $this
    ]);
}

public function getInputHtml($value, ElementInterface $element = null): string
{
    return Craft::$app->getView()->renderTemplate('my-module/my-field/input', [
        'name' => $this->handle,
        'value' => $value,
        'field' => $this,
        'element' => $element,
    ]);
}
```

You'll notice special-cases for `getFrontEndInputTemplatePath()` and `getEmailTemplatePath()`. Whilst there are `getFrontEndInputHtml()` and `getEmailHtml()` functions, they should not be overwritten in most cases. This is because Formie will determine where to look for these templates, either in its own default templates, or the folder where custom templates are defined. As such, defining `getFrontEndInputTemplatePath()` and `getEmailTemplatePath()` to the path of Twig files is all that is needed.

In simple terms - if you are creating a third-party field for Formie, for general distribution, do not alter `getFrontEndInputHtml()`. If you are using a custom field just on your project, it may be a valid case.
