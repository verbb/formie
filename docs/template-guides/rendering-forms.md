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

If you're using a [Form Field](docs:template-guides/selecting-forms) in an entry or other element, you can get the [Form](docs:developers/form) object from that field. For example, you might have a Form field with the handle `myFormFieldHandle` added to a specific entry element.

```twig
{% set form = entry.myFormFieldHandle.one() %}

{{ craft.formie.renderForm(form) }}
```

If you are using custom templates, you can also pass in a number of options to the rendering function. For help on how these options should be structured, refer to the [Rendering Options](docs:template-guides/rendering-options).

```twig
{% set options = {
    someOption: 'someValue',
} %}

{{ craft.formie.renderForm('contactForm', options) }}
```

## Override Form Attributes and Classes
You can use the rendering options to override any HTML attributes, data attributes and classes on the `<form>` element.

```twig
{% set options = {
    formClasses: [
        'my-custom-class',
    ],
    formDataAttributes: {
        'new-attribute': 'some-value',
    },
    formAttributes: {
        'title': 'Some Title',
    },
} %}

{{ craft.formie.renderForm('contactForm', options) }}
```

The above would produce:

```twig
<form class="fui-form fui-labels-above-input my-custom-class" data-new-attribute="some-value" title="Some Title">
    ...
```

## Override Form Settings
You can also dynamically override any settings for the form.

```twig
{% set form = craft.formie.forms.handle('contactForm').one() %}

{% do form.setSettings({
    {# Set the redirect URL to something static #}
    redirectUrl: 'https://google.com',

    {# Or, set the redirect URL to include a query param to the submission #}
    redirectUrl: form.getRedirectUrl() ~ '?submissionId={id}',
}) %}

{{ craft.formie.renderForm(form) }}
```

The above would override the redirect URL for the form, regardless of what is defined in the form's settings. See the [Form Settings](docs:developers/form#form-settings) docs for a full list of available settings to override.

For more fine-grained control over rendering of a form, see [Rendering Pages](docs:template-guides/rendering-pages) and [Rendering Fields](docs:template-guides/rendering-fields)

## Override Form ID
You can also override the Form's ID, which is an important factor in rendering the form in HTML, but in connecting JavaScript functionality to fields and the form itself.

By default, the ID of a form is randomly generated, to ensure each form (even the same one) is uniquely rendered on the page. The default format looks similar to `formie-form-35415ffccebce54e6`.

```twig
{% set form = craft.formie.forms.handle('contactForm').one() %}

{% do form.setFormId('formie-form-special-contact-form') %}

{{ craft.formie.renderForm(form) }}
```

Do note that you **must** prefix your ID with `formie-form-`, as Formie's JavaScript relies on this to connect functionality up. This will be improved in the next major version of Formie as a breaking change, to provide greater flexibility.
