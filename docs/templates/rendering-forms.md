# Rendering Forms

Rendering the whole form is the normal starting point.

It is the best starting point when you want Formie to handle the form wrapper, pages, fields, buttons, and assets for you.

```twig
{{ craft.formie.renderForm('contactForm') }}
```

You can also pass a `Form` object instead of a handle.

```twig
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}

{{ craft.formie.renderForm(form) }}
```

If you're using a Form Field in an entry or other element, you can get the Form object from that field. For example, you might have a Form field with the handle `myFormFieldHandle` added to a specific entry element.

```twig
{% set form = entry.myFormFieldHandle.one() %}

{{ craft.formie.renderForm(form) }}
```

## Render options

You can pass render options as the second argument.

```twig
{{ craft.formie.renderForm('contactForm', {
    fieldNamespace: 'myFields',
    themeConfig: {
        form: {
            attributes: {
                class: 'space-y-6',
            },
        },
    },
}) }}
```

See [Render Options](/templates/render-options) for the shared options supported across Formie’s render helpers.
