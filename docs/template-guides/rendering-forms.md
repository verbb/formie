# Rendering Forms
Showing the form in your templates is the first step in getting Formie's forms working with your site. Rendering an entire form will include all pages, rows, columns, fields, buttons, captchas and more. Combined with the [Form Templates](docs:feature-tour/form-templates), it should be the only Twig code you need to get forms showing on your site.

The easiest way to output your form is to use the handle of a form:

```twig
{{ craft.formie.renderForm('contactForm') }}
```

You can optionally provide a [Form](docs:developers/form) object in the same way.

```twig
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}

{{ craft.formie.renderForm(form) }}
```

If you are using custom templates, you can also pass in a number of options to the rendering function. For help on how these options should be structured, refer to the [Rendering Options](docs:template-guides/rendering-options).

```twig
{% set options = {
    someOption: 'someValue',
} %}

{{ craft.formie.renderForm('contactForm', options) }}
```

For more fine-grained control over rendering of a form, see [Rendering Pages](docs:template-guides/rendering-pages) and [Rendering Fields](docs:template-guides/rendering-fields)
