# Rendering Forms
Showing the form in your templates is the first step in getting Formie's forms working with your site. Rendering an entire form will include all pages, rows, columns, fields, buttons, captchas and more.

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

## Render Options
A second argument to `renderForm()` allows you to pass in variables used as [Render Options](docs:theming/render-options).

```twig
{% set renderOptions = {
    fieldNamespace: 'myCustomNamespace',

    themeConfig: {
        form: {
            attributes: {
                class: 'my-custom-class',
                'data-new-attribute': 'some-value',
            },
        },
    },
} %}

{{ craft.formie.renderForm('contactForm', renderOptions) }}
```

## Override Form Settings
You can dynamically override any settings for the form using `form.setSettings()`.

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

For more fine-grained control over rendering of a form, see [Rendering Pages](docs:template-guides/rendering-pages) and [Rendering Fields](docs:template-guides/rendering-fields).
