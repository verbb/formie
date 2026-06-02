# Theme Config
Using theme config is the recommended way to customize Formie's rendered HTML.

It gives you control over the HTML tags and attributes used to build a form, including pages, rows, fields, buttons, and more. That makes it useful for changing classes, adding attributes, adjusting wrappers, or changing the tag used for a particular part of the output.

For this reason, it's well-suited to being used to output opinionated class attributes used by utility CSS frameworks like [Tailwind](https://tailwindcss.com/), or other frameworks like [Bootstrap](https://getbootstrap.com/).

For complete framework presets and larger examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

> [!NOTE]
> Read [Theming Overview](/theming/overview) first if you want to compare theme config with template overrides and custom rendering.

## Overview
To gain a better understanding of what theme config is for, let's walk through the HTML output of a form. Take for example the default structure below (without every attribute shown):

```html
<form class="formie-form" data-formie-form>
    <div class="formie-pages">
        <div class="formie-page">
            <div class="formie-page-container">
                <div class="formie-row">
                    <div class="formie-field">
                        <div class="formie-field-container">
                            <label class="formie-label">My Example Field</label>
                            <div class="formie-instructions">Some instructions</div>

                            <div class="formie-field-control">
                                <input type="text">
                            </div>
                        </div>

                       <div class="formie-field-errors">
                           <div class="formie-field-error">
                        </div>
                    </div>
```

Here, we have outlined the full structure of a form, containing a single field which would be rendered by `craft.formie.renderForm()`. While there are many HTML elements, each serve a purpose with styling, semantic structure, accessibility and general organisation.

Let's say you want to add a `my-form` ID attribute to the `<form>` element. You could use [Custom Rendering](/theming/custom-rendering) or [Template Overrides](/theming/template-overrides), but that is a heavier approach and gives you more template maintenance to keep up with.

Instead, we can use theme config to manipulate the attributes for this form.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        form: {
            attributes: {
                id: 'my-form',
            },
        },
    },
}) }}

{# Rendering #}
<form id="my-form" class="formie-form" data-formie-form>
    <div class="formie-pages">
        <div class="formie-page">
            ...
```

Here is a more involved example:

- Remove all of Formie's classes on the `<form>` element and add `id="my-form"`
- Change one wrapper to a `<fieldset>`
- Add padding to the field wrapper
- Add a red border to the `<input>` element for all fields (Tailwind)

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        form: {
            reset: true,
            attributes: {
                id: 'my-form',
            },
        },

        pages: {
            tag: 'fieldset',
        },

        field: {
            attributes: {
                class: 'p-4 w-full mb-4',
            },
        },

        fieldInput: {
            attributes: {
                class: 'border border-red-500',
            },
        },
    },
}) }}

{# Rendering #}
<form id="my-form">
    <fieldset class="formie-pages">
        <div class="formie-page">
            <div class="formie-page-container">
                <div class="formie-row">
                    <div class="formie-field p-4 w-full mb-4">
                        <div class="formie-field-container">
                            <label class="formie-label">My Example Field</label>
                            <div class="formie-instructions">Some instructions</div>

                            <div class="formie-field-control">
                                <input type="text" class="border border-red-500">
                            </div>
                        </div>

                       <div class="formie-field-errors">
                           <div class="formie-field-error">
                        </div>
                    </div>
```

## Theme Tags
As you can see above, you pass a Twig object with keys for different parts of the rendered HTML. Each key can define attributes, a different tag, or even remove that element entirely by returning `false` or `null`.

Formie exposes several of these theme tags. Some apply to the overall form, some apply to all fields, and some are specific to particular field types. Every tag definition can use the following properties:

| Attribute | Type | Description
| - | - | -
| `reset` | `Boolean` | Whether to retain or remove Formie's default `formie-*` classes for the element. |
| `tag` | `String` | The HTML tag to use for that element. |
| `attributes` | `Object` | A collection of HTML attributes for the element. Values such as `class`, `data`, `style`, and `aria` can be arrays or nested objects. This works much like Craft's [`attr`](https://craftcms.com/docs/4.x/functions.html#attr) helper. |
| `cssVars` | `Object` | CSS custom properties to merge into the element's inline style. |
| `prepend` / `append` | `Array|Object` | Extra content nodes to inject before or after the element content. |

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        pages: {
            tag: 'fieldset',
            reset: true,
            attributes: {
                class: ['one', 'two'],
                disabled: true,
                readonly: false,
                style: {
                    'background-color': 'red',
                    'font-size': '20px',
                },
            },
        },
    },
}) }}
```

The available tags are grouped below.

### Form tags
- `form`
- `formHeader`
- `formMessagesTop`
- `formNavigation`
- `formBody`
- `formFooter`
- `formMessagesBottom`
- `pages`
- `messageError`
- `messageSuccess`
- `formTitle`
- `pageTabs`
- `pageTab`
- `pageTabLink`
- `page`
- `pageContainer`
- `pageHeader`
- `pageBody`
- `pageFooter`
- `pageCaptchas`
- `pageButtons`
- `pageTitle`
- `rows`
- `row`
- `captchaContainer`
- `buttonContainer`
- `submitButton`
- `saveButton`
- `backButton`
- `progressWrapper`
- `progress`
- `progressContainer`
- `progressValue`
- `errors`
- `error`

### Shared field tags
- `field`
- `fieldLayout`
- `fieldLabel`
- `fieldRequired`
- `fieldOptional`
- `fieldInstructions`
- `fieldContent`
- `fieldControl`
- `fieldErrors`
- `fieldError`
- `subFieldRows`
- `subFieldRow`
- `nestedFieldRows`
- `nestedFieldRow`

### Address Field
- `subFieldRows`
- `subFieldRow`
- `fieldInput`

### Agree Field
- `fieldOptions`
- `fieldInput`
- `fieldOption`
- `fieldOptionLabel`

### Checkboxes Field
- `fieldInput`
- `fieldOptions`
- `fieldOption`
- `fieldOptionLabel`

### Date/Time Field
- `subFieldRows`
- `subFieldRow`
- `fieldInput`

### File Upload Field
- `fieldInput`
- `fieldSummary`
- `fieldSummaryContainer`
- `fieldSummaryItem`

### Group Field
- `nestedFieldRows`
- `nestedFieldRow`
- `nestedFieldContainer`

### Heading Field
- `fieldHeading`

### Multi-Line Text Field
- `fieldInput`
- `fieldLimit`
- `fieldRichText`

### Name Field
- `subFieldRows`
- `subFieldRow`
- `fieldInput`

### Phone Field
- `fieldInput`
- `fieldCountryInput`

### Radio Field
- `fieldInput`
- `fieldOptions`
- `fieldOption`
- `fieldOptionLabel`

### Repeater Field
- `nestedField`
- `nestedFieldWrapper`
- `nestedFieldRows`
- `nestedFieldRow`
- `nestedFieldContainer`
- `fieldAddButton`
- `fieldRemoveButton`

### Section Field
- `fieldSection`

### Signature Field
- `fieldCanvas`
- `fieldInput`
- `fieldRemoveButton`

### Single-Line Text Field
- `fieldInput`
- `fieldLimit`

### Summary Field
- `fieldSummary`
- `fieldSummaryBlocks`
- `fieldSummaryBlock`
- `fieldSummaryContainer`
- `fieldSummaryHeading`
- `fieldSummaryItem`
- `fieldSummaryLabel`
- `fieldSummaryValue`

### Table Field
- `fieldTableWrapper`
- `fieldTable`
- `fieldTableHeader`
- `fieldTableHeaderRow`
- `fieldTableHeaderColumn`
- `fieldTableBody`
- `fieldTableBodyRow`
- `fieldTableBodyColumn`
- `fieldAddButton`
- `fieldRemoveButton`
- `fieldTableRemoveColumn`
- `tableCheckboxInput`
- `tableColorInput`
- `tableDateInput`
- `tableEmailInput`
- `tableHeadingInput`
- `tableMultilineInput`
- `tableNumberInput`
- `tableSelectInput`
- `tableSinglelineInput`
- `tableTimeInput`
- `tableUrlInput`

### Captcha and integration-specific tags

- `captcha`
- `stripePlaceholder`

### Targeting a field type

You can also target a field type directly. This lets you apply the same tag config only to one type of field instead of all fields.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        singleLineText: {
            field: {
                attributes: {
                    class: 'just-for-text',
                },
            },
        },
    },
}) }}
```

These keys use the field class' `camelCase` name. Available field type keys include:

- `address`
- `agree`
- `calculations`
- `categories`
- `checkboxes`
- `dateTime`
- `dropdown`
- `emailAddress`
- `entries`
- `fileUpload`
- `forms`
- `group`
- `heading`
- `hiddenField`
- `html`
- `multiLineText`
- `name`
- `number`
- `password`
- `payment`
- `phoneNumber`
- `products`
- `radioButtons`
- `recipients`
- `repeater`
- `section`
- `signature`
- `singleLineText`
- `submissions`
- `summary`
- `table`
- `tags`
- `users`
- `variants`

Some fields also expose their inner sub-fields as their own targetable keys.

Available sub-field keys include:

- `address1`
- `address2`
- `address3`
- `addressAutoComplete`
- `addressCity`
- `addressCountry`
- `addressState`
- `addressZip`
- `dateDate`
- `dateTime`
- `dateAmPmDropdown`
- `dateAmPmNumber`
- `dateDayDropdown`
- `dateDayNumber`
- `dateDropdown`
- `dateHourDropdown`
- `dateHourNumber`
- `dateMinuteDropdown`
- `dateMinuteNumber`
- `dateMonthDropdown`
- `dateMonthNumber`
- `dateNumber`
- `dateSecondDropdown`
- `dateSecondNumber`
- `dateYearDropdown`
- `dateYearNumber`
- `nameFirst`
- `nameLast`
- `nameMiddle`
- `namePrefix`

That lets you target one part of the field without affecting the rest.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        address1: {
            fieldInput: {
                attributes: {
                    class: 'street-line',
                },
            },
        },

        addressCountry: {
            fieldInput: {
                attributes: {
                    class: 'country-select',
                },
            },
        },
    },
}) }}
```

The same pattern applies to other fields with inner sub-fields, such as Name and some Date/Time input modes.


## Examples
Below are some common examples of what theme config can do.

### Appending Attributes
We can append attributes to an element, ensuring that existing ones are kept in-place.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        field: {
            attributes: {
                class: 'appended-class',
                'data-custom': 'my-field',
            },
        },
    },
}) }}

{# Rendering #}
<div class="formie-field appended-class" data-formie-field-type="single-line-text" data-custom="my-field">
    ...
```

### Resetting Classes
If you do not want to retain Formie's default classes on a tag, use `reset`.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        field: {
            reset: true,
            attributes: {
                class: 'my-field',
            },
        },
    },
}) }}

{# Rendering #}
<div class="my-field">
    ...
```

### Removing Tags
If you do not want to render a tag at all, you can return a falsey value.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        fieldInstructions: false,
        fieldErrors: null,
    },
}) }}

{# Rendering #}
<div class="formie-field">
    <label class="formie-label">My Example Field</label>
    <div class="formie-field-content">
        <div class="formie-field-control">
            <input type="text">
        </div>
    </div>
</div>
```

### Conditional values
Theme config supports structured conditional values using `if`, `then`, and `else`.

For example, if you want to add a class only when a Recipients field is hidden:

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        recipients: {
            field: {
                attributes: {
                    class: [
                        'hidden-field',
                        {
                            if: 'field.isHidden',
                            then: 'is-hidden',
                        },
                    ],
                },
            },
        },
    },
}) }}
```

The `hidden-field` class is always added, while `is-hidden` is only added when `field.isHidden` is truthy.

You can also use more explicit comparisons:

```twig
{
    if: {
        context: 'page.buttonsPosition',
        equals: 'left',
    },
    then: 'buttons-left',
    else: 'buttons-other',
}
```

The available condition context comes from the current render state, including `form`, `field`, `page`, `currentPage`, `row`, and `submission`.

## Ready-Made Configs
We have also put together a few full-featured theme config examples you can use as a starting point. Each one removes Formie's default classes and replaces them with framework-specific ones.

- [Tailwind](https://github.com/verbb/formie-theme-configs/blob/formie-3/tailwind/index.html)
- [Bootstrap](https://github.com/verbb/formie-theme-configs/blob/formie-3/bootstrap/index.html)

> [!TIP]
> If you build a useful theme config for your project, it can be a good starting point for future forms and future projects too.

## Config Definitions
There are 3 methods for how you define a theme config, which are shown below in order of priority (with the first being the lowest priority).

1. Plugin configuration settings with PHP.
2. Twig rendering using `craft.formie.renderForm()`.
3. Event definitions in a PHP module.

Although each method is a different way to define a theme config, it all ends up being treated the same - just a different way of registering this configuration. Your project could even use a combination of all methods!

### Render-time (Twig)
Used with `craft.formie.renderForm()` through [Render Options](/templates/render-options), this is the most direct way to provide theme config in a template.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        form: {
            attributes: {
                class: 'border border-red-500',
            },
        },
        field: {
            attributes: {
                class: 'border border-blue-500',
            },
        },
        fieldInput: {
            tag: 'div',
            reset: true,
            attributes: {
                class: 'border border-blue-500',
            },
        },
    },
}) }}
```

### Events (PHP)
You can also use [Form Events](/developers/events/form-events) to define theme config in PHP. This gives you more control than Twig, but it also means writing module or plugin code.

For form-level tags:

```php
use verbb\formie\elements\Form;
use verbb\formie\events\ModifyFormSlotTagEvent;
use yii\base\Event;

Event::on(Form::class, Form::EVENT_MODIFY_SLOT_TAG, function(ModifyFormSlotTagEvent $event) {
    $form = $event->form;
    $tag = $event->tag;
    $key = $event->key;
    $context = $event->context;

    // For the field `<form>` element, change the tag and add attributes
    if ($event->key === 'form') {
        $event->tag->tag = 'div';
        $event->tag->attributes['class'][] = 'p-4 w-full mb-4';
    }
});
```

For field-level tags:

```php
use verbb\formie\base\Field;
use verbb\formie\fields\SingleLineText;
use verbb\formie\events\ModifyFieldSlotTagEvent;
use yii\base\Event;

// Provide config for all fields
Event::on(Field::class, Field::EVENT_MODIFY_SLOT_TAG, function(ModifyFieldSlotTagEvent $event) {
    $field = $event->field;
    $tag = $event->tag;
    $key = $event->key;
    $context = $event->context;

    // For the main field element, replace the class attribute entirely
    if ($event->key === 'field') {
        $event->tag->attributes['class'] = 'p-4 w-full mb-4';
    }

    // For the field instructions element, don't render the element
    if ($event->key === 'fieldInstructions') {
        $event->tag = null;
    }
});

// Single-line-text field-specific config
Event::on(SingleLineText::class, SingleLineText::EVENT_MODIFY_SLOT_TAG, function(ModifyFieldSlotTagEvent $event) {
    $field = $event->field;
    $tag = $event->tag;
    $key = $event->key;
    $context = $event->context;

    // For the field `<input>` element, change the tag and add attributes
    if ($event->key === 'fieldInput') {
        $event->tag->tag = 'span';
        $event->tag->attributes['class'][] = 'p-4 w-full mb-4';
    }
});
```

### Plugin Configuration (PHP)
You can also define theme config globally through the [plugin configuration](/get-started/configuration) file. Because this has the lowest priority, render-time config and events can still override it.

```php
<?php

return [
    'themeConfig' => [
        // For the field `<form>` element, change the tag and add attributes
        'form' => [
            'reset' => true,
            'tag' => 'div',
            'attributes' => [
                'class' => 'p-4 w-full mb-4',
            ],
        ],

        // For the main field element
        'field' => [
            'attributes' => [
                'class' => 'p-4 w-full mb-4',
            ],
        ],

        // Specifically for a single-line-text field, we want the label uppercased
        'singleLineText' => [
            'fieldLabel' => [
                'attributes' => [
                    'class' => 'uppercase',
                ],
            ],
        ],
    ],
];
```

### Combining with Template Overrides
Even if you want to use [Template Overrides](/theming/template-overrides), you can still keep theme config in play. For example, the default Twig template for a Single-Line Text field is very small:

```twig
{{ fieldtag('fieldInput', {
    value: value ?? false,
}) }}
```

Well, that's certainly short and sweet! All the HTML for this field is actually defined at the class level, so there's very little HTML we need to write to output the `<input>` HTML element. This allows us to provide you with the ability to easily override our default config.

You have two choices here; 1. Add your own attributes to the `fieldInput` tag, or 2. Write your own HTML.

### Retain Theme Config
It might be a good idea to have your custom changes still allow theme config to be used. You can do this by extending the theme config used by the `fieldtag()` Twig function.

:::tip
The `fieldtag()` Twig function and the `{% fieldtag %}` Twig tag are almost identical to Craft's own [`tag()`](https://craftcms.com/docs/4.x/functions.html#tag) and [`{% tag %}`](https://craftcms.com/docs/4.x/tags.html#tag) functionality.
:::

```twig
{{ fieldtag('fieldInput', {
    value: value ?? false,
    class: 'my-custom-class',
    'data-custom-field': true,
}) }}

{# or #}
{% fieldtag 'fieldInput' with {
    value: value ?? false,
    class: 'my-custom-class',
    'data-custom-field': true,
} %}
    Some content
{% endfieldtag %}
```

#### BYO Twig
Of course, there's nothing stopping you from writing your own HTML and Twig, you'll just lose the ability to use theme config for this field. However, it may very well be easier if you have complex logic for this field, want full control over output, or just find it easier to manage.

```twig
{# Just HTML #}
<input type="text" value="{{ value ?? false }}" class="my-custom-class" data-custom-field>

{# Using `tag()` #}
{{ tag('input', {
    type: 'text',
    value: value ?? false,
    class: 'my-custom-class',
    'data-custom-field': true,
}) }}
```
