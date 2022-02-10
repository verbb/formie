# Rendering Options
There are a number of options you can pass into the [`craft.formie.renderForm()`](docs:template-guides/rendering-forms) function.


## Base Options
These options are used by the default Formie template. If you use your own template, these options will not necessarily have an effect.

Option | Description
--- | ---
`fields` | Affect the attributes of specific fields. Accepts a [Fields Options](#fields-options) object as the value.
`buttons` | Affect the attributes of the submit/next page and previous page buttons. Accepts a [Buttons Options](#buttons-options) object as the value.
`fieldTag` | Set the tag for the outer-most field container. This is `div` by default.
`fieldNamespace` | Set the namespace used by all fields. This is `fields` by default.
`formClasses` | Provide an array of classes to be merged on the `<form>` element.
`formDataAttributes` | Provide an object of data attributes to be merged on the `<form>` element.
`formAttributes` | Provide an object of attributes to be merged on the `<form>` element.

An example modifying the attributes for the `<form>` element.

```twig
{{ craft.formie.renderForm('contactForm', {
    formAttributes: {
        class: ['custom-form', 'another-class'],
        'data-site-form': true,
    },
}) }}
```


## Buttons Options
You can affect the attributes of the page buttons. These attributes are merged into the default buttons attributes and rendered using the [`attr()`](https://docs.craftcms.com/v3/dev/functions.html#attr) function.

Option | Description
--- | ---
`submit` | Set the attributes of the submit/next page button.
`prev` | Set the attributes of the previous page button.

For instance, maybe we want to add some classes or attributes to the submit button on a form. For multi-page forms, this would be for every page's submit button.

```twig
{{ craft.formie.renderForm('contactForm', {
    buttons: {
        submit: {
            class: ['custom-submit-btn', 'another-class'],
            'data-form-submit-btn': true,
        },
        prev: {
            class: 'custom-back-btn',
        },
    },
}) }}
```

## Fields Options

Option | Description
--- | ---
`fieldHandle` | Set the key to the handle of the field you would like to affect. Accepts a [Field Options](#field-options) object as the value

### Field Options

Option | Description
--- | ---
`fieldTag` | Set the tag for the outer-most field container. This is `div` by default. Takes precedence over the [Base Options](#base-options) `fieldTag`.
`attributes` | Set the attributes of the outer-most field container. These attributes are merged into the default field container attributes and rendered using the [`attr()`](https://docs.craftcms.com/v3/dev/functions.html#attr) function.
