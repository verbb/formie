# Theme Config
Using a Theme Config to render your form is the recommended approach for quickly customising your form's output HTML. Put simply, it provides you an easy way to control and manipulate each HTML element and attribute that's used to build a form, through its pages, rows, fields, buttons and more. This allows you to change the attributes (commonly class attributes) for components like the submit button, field `<input>` elements, and even the `<form>` element itself.

For this reason, it's well-suited to being used to output opinionated class attributes used by utility CSS frameworks like [Tailwind](https://tailwindcss.com/), or other frameworks like [Bootstrap](https://getbootstrap.com/).

:::warning
We recommend reading the [theming overview](docs:theming/overview) docs before getting started, for an explanation of theme config compared to other methods of theming forms.
:::

## Overview
To gain a better understanding of what theme config is for, let's walk through the HTML output of a form. Take for example the default structure below (without every attribute shown):

```twig
<div class="fui-i">
    <form class="fui-form">
        <div class="fui-form-container">
            <div class="fui-page">
                <div class="fui-page-container">
                    <div class="fui-row fui-page-row">
                        <div class="fui-field">
                            <div class="fui-field-container">
                                <label class="fui-label">My Example Field</label>
                                <div class="fui-instructions">Some instructions</div>

                                <div class="fui-input-container">
                                    <input type="text">
                                </div>
                            </div>

                           <div class="fui-errors">
                               <div class="fui-error">
                            </div>
                        </div>
```

Here, we have outlined the full structure of a form, containing a single field which would be rendered by `craft.formie.renderForm()`. While there are many HTML elements, each serve a purpose with styling, semantic structure, accessibility and general organisation.

Let's say, we want to add a `my-form` ID attribute to the `<form>` element. Whilst we could use [custom rendering](docs:theming/custom-rendering) or [template overrides](docs:theming/template-overrides) to gain control over the templating used to generate the `<form>` element, that's quite heavy-handed, and relies on you now maintaining that template as Formie changes.

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
<div class="fui-i">
    <form id="my-form" class="fui-form">
        <div class="fui-form-container">
            <div class="fui-page">
                ...
```

Let's try a few more things at once for fun:

- Remove the `<div class="fui-i">`
- Remove all of Formie's classes on the `<form>` element and add `id="my-form"`
- Change `<div class="fui-form-container">` to `<fieldset class="fui-form-container">`
- And some padding to the field wrapper (Tailwind)
- Add a red border to the `<input>` element for all fields (Tailwind)

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        formWrapper: false,

        form: {
            resetClass: true,
            attributes: {
                id: 'my-form',
            },
        },

        formContainer: {
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
    <fieldset class="fui-form-container">
        <div class="fui-page">
            <div class="fui-page-container">
                <div class="fui-row fui-page-row">
                    <div class="fui-field p-4 w-full mb-4">
                        <div class="fui-field-container">
                            <label class="fui-label">My Example Field</label>
                            <div class="fui-instructions">Some instructions</div>

                            <div class="fui-input-container">
                                <input type="text" class="border border-red-500">
                            </div>
                        </div>

                       <div class="fui-errors">
                           <div class="fui-error">
                        </div>
                    </div>
```

## Theme Tags
As you can see above, we're passing in a Twig object with keys for certain components of the rendered HTML, and either providing attributes as a nested Twig object, or the ability to exclude them from render altogether (returning `false` or `null`).

Formie has several of these "theme tags" defined, to allow us to define an API of sorts for how a form is structured, and how you can provide definitions for them. Some are for the overall form, for fields, and for specific field types. Every tag definition can have the following set:

| Attribute | Type | Description
| - | - | -
| `resetClass` | `Boolean` | Whether to retain or remove any `fui-*` classes for the element.
| `tag` | `String` | the valid HTML to be used for the HTML element.
| `attributes` | `Boolean` | A collection of valid HTML attributes for the HTML element. Some items can be arrays like `class`, `data`, `style` or `aria`. This works exactly like Craft's [`attr`](https://craftcms.com/docs/4.x/functions.html#attr) function.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        formContainer: {
            tag: 'fieldset',
            resetClass: true,
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

For a full list of available tags, refer to the below:

### Form Tags
- `formWrapper`
- `form`
- `formContainer`
- `alertError`
- `alertSuccess`
- `errors`
- `error`
- `formTitle`

## Page Tags
- `page`
- `pageContainer`
- `pageTitle`
- `row`

## Page Tab Tags
- `pageTabs`
- `pageTab`
- `pageTabLink`

## Progress Tags
- `progressWrapper`
- `progress`
- `progressContainer`
- `progressValue`

## Buttons
- `buttonWrapper`
- `submitButton`
- `backButton`

## Field Tags
- `field`
- `fieldContainer`
- `fieldLabel`
- `fieldInstructions`
- `fieldInputContainer`
- `fieldInput`
- `fieldErrors`
- `fieldError`

### Address Field
- `subFieldRows`
- `subFieldRow`

### Agree Field
- `fieldOption`
- `fieldOptionLabel`

### Checkboxes Field
- `fieldOptions`
- `fieldOption`
- `fieldOptionLabel`

### Date/Time Field
- `subFieldRows`
- `subFieldRow`

### File Upload Field
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
- `fieldLimit`

### Name Field
- `subFieldRows`
- `subFieldRow`

### Radio Field
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
- `fieldRemoveButton`

### Single-Line Text Field
- `fieldLimit`

### Summary Field
- `fieldSummaryBlocks`
- `fieldSummaryBlock`
- `fieldSummaryHeading`

### Table Field
- `fieldTable`
- `fieldTableHeader`
- `fieldTableHeaderRow`
- `fieldTableHeaderColumn`
- `fieldTableBody`
- `fieldTableBodyRow`
- `fieldTableBodyColumn`
- `fieldAddButton`
- `fieldRemoveButton`

### Field Types
In addition, you can also target fields on a particular type. You can then provide all the same field-level tag configs that you would for general fields.

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

These tags are the `camelCase` string for the field type class.
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
- `group`
- `heading`
- `hiddenField`
- `html`
- `multiLineText`
- `name`
- `number`
- `payment`
- `password`
- `phoneNumber`
- `radioButtons`
- `recipients`
- `repeater`
- `section`
- `signature`
- `singleLineText`
- `summary`
- `table`
- `tags`
- `users`
- `products`
- `variants`

## Examples
Below are some common examples to better understand what you can do with theme config. 

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
<div class="fui-field appended-class" data-field-type="single-line-text" data-custom="my-field">
    ...
```

### Resetting Classes
If you don't want to retain Formie's default classes on tag, you can remove them by passing `resetClass`.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        field: {
            attributes: {
                resetClass: true,
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
If you don't want to render a tag at all, you can return a falsey value.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        fieldContainer: false,
        fieldInputContainer: null,
    },
}) }}

{# Rendering #}
<div class="fui-field">
    <label class="fui-label">My Example Field</label>
    <div class="fui-instructions">Some instructions</div>

    <input type="text">
</div>
```

### Resetting All Classes
Like using `resetClass` on an individual tag, you can use `resetClasses` at the theme config level to remove all classes for everything. This can provide you with a blank-slate for you to theme. No other attributes are reset to ensure accessibility and Formie's JS functionality.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        resetClasses: true,
    },
}) }}

{# Rendering #}
<div>
    <form id="uniqueId" method="post" data-fui-form>
        <div>
            <div>
                <div>
                    <div>
                        <div data-field-handle="example" data-field-type="single-line-text">
                            <div>
                                <label for="uniqueId">My Example Field</label>
                                <div>Some instructions</div>

                                <div>
                                    <input id="uniqueId" type="text" name="fields[example]">
                                </div>
                            </div>
                        </div>
                    ...
```

### Advanced Usage with Twig
You can also include Twig templating within the value of a class for even more powerful usage.

Let's take for example the Recipients field. There's 4 different display options for this field; Dropdown, Hidden, Checkboxes and Radio Buttons. In the case of when this field is Hidden, we clearly don't want the field to be visible (but still rendered), but there's not an easy way to change classes depending on settings on the field.

Fortunately, we've included the ability to write Twig in class values.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        recipients: {
            field: {
                attributes: {
                    class: [
                        'hidden-field',
                        "{{ field.getIsHidden() ? 'is-hidden' : false }}",
                    ],
                },
            },
        },
    },
}) }}
```

Here, we're targeting all Recipients fields, and the `field` theme tag. We're applying a `hidden-field` class on this tag, but you'll also notice the Twig code surrounded by `"` - `{{ field.getIsHidden() ? 'is-hidden' : false }}`. When you provide a Twig template as a string, Formie will evaluate that Twig code in the context of the current field. 

You'll notice we're calling `field.getIsHidden()` which is a [Field](docs:developers/field) method. You could call **any** property or method on this field if your template. We're then using a ternary operator to return `is-hidden` if the `getIsHidden()` function returns true, or `false` if not.

Your Twig template can be whatever you need, just ensure to wrap is as a string with either `"` or `'` characters.

## Ready-Made Configs
We've put together a few full-featured and drop-in theme config's for you to use in your forms. Each example completely removes Formie's default `fui-*` classes. You're welcome to use them as-is, or modify them for your next project.

- [Tailwind](https://github.com/verbb/formie-theme-configs/blob/main/tailwind/index.html)
- [Bootstrap](https://github.com/verbb/formie-theme-configs/blob/main/bootstrap/index.html)

:::tip
Have a theme config you'd like to share? [Drop us a line](/contact).
:::

## Config Definitions
There are 3 methods for how you define a theme config, which are shown below in order of priority (with the first being the lowest priority).

1. Plugin configuration settings with PHP.
2. Twig rendering using `craft.formie.renderForm()`.
3. Event definitions in a PHP module.

Although each method is a different way to define a theme config, it all ends up being treated the same - just a different way of registering this configuration. Your project could even use a combination of all methods!

### Render-time (Twig)
Used with your `craft.formie.renderForm()` in the form of [Render Options](docs:theming/render-options), you can provide a config for your form at render time with Twig.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        formWrapper: {
            attributes: {
                class: 'border border-green-500',
            },
        },
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
            resetClass: true,
            attributes: {
                class: 'border border-blue-500',
            },
        },
    },
}) }}
```

### Events (PHP)
You can also use [Events](docs:developers/events) to define your theme config in the same manner. Using PHP gives you more control and flexibility than Twig, but does require knowledge of PHP and Craft's modules.

For form-level tags:

```php
use verbb\formie\elements\Form;
use verbb\formie\events\ModifyFormHtmlTagEvent;
use yii\base\Event;

Event::on(Form::class, Form::EVENT_MODIFY_HTML_TAG, function(ModifyFormHtmlTagEvent $event) {
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
use verbb\formie\base\FormField;
use verbb\formie\fields\formfields\SingleLineText;
use verbb\formie\events\ModifyFieldHtmlTagEvent;
use yii\base\Event;

// Provide config for all fields
Event::on(FormField::class, FormField::EVENT_MODIFY_HTML_TAG, function(ModifyFieldHtmlTagEvent $event) {
    $field = $event->field;
    $tag = $event->tag;
    $key = $event->key;
    $context = $event->context;

    // For the main field element, replace the class attribute entirely
    if ($event->key === 'field') {
        $event->tag->attributes['class'] = 'p-4 w-full mb-4';
    }

    // For the inner field wrapper, don't render the element
    if ($event->key === 'fieldContainer') {
        $event->tag = null;
    }
});

// Single-line-text field-specific config
Event::on(SingleLineText::class, SingleLineText::EVENT_MODIFY_HTML_TAG, function(ModifyFieldHtmlTagEvent $event) {
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
You can also define your config at the global plugin level through the [plugin configuration](docs:get-started/configuration) file. Because this has the least priority, all other methods can override your definition here. This allows you to create sane defaults at the plugin level - even across multiple projects - and tweak them as needed.

```php
<?php

return [
    'themeConfig' => [
        // Remove all of Formie's classes by default
        'resetClasses' => true,

        // For the field `<form>` element, change the tag and add attributes
        'form' => [
            'attributes' => [
                'class' => 'p-4 w-full mb-4',
                'tag' => 'div',
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
Even if you want to use [template overrides](docs:theming/template-overrides), you can still use theme config in a non-breaking manner. Let's look at the default Twig template for a Single-Line Text field.

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
