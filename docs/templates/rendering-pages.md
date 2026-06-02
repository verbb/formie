# Rendering Pages

Render pages manually when you want control over the outer `<form>` element but still want Formie to render each page.

This sits in the middle ground between full automatic rendering and fully custom rendering.

```twig
{% set form = craft.formie.forms.handle('contactForm').one() %}

{{ craft.formie.formAssets(form) }}

<form method="post" data-fui-form="{{ form.configJson }}">
    {{ csrfInput() }}
    {{ actionInput('formie/submissions/submit') }}
    {{ hiddenInput('handle', form.handle) }}

    {% for page in form.getPages() %}
        {{ craft.formie.renderPage(form, page) }}
    {% endfor %}
</form>
```

## What you still need

Once you stop using `renderForm()`, you become responsible for the outer form setup.

That usually means:

- outputting the `<form>` tag yourself
- including the required hidden inputs
- keeping the `data-fui-form` attribute with the form config on the form element
- outputting assets yourself

If that `data-fui-form` attribute is missing, Formie's browser startup cannot initialize the form properly.

## Assets

Use `craft.formie.formAssets(form)` before the form markup when you are assembling the form yourself.

That gives you the right browser CSS, startup script, and translation seed without having to fall back to the older asset helper pattern.

See [Assets](/templates/assets) for the available helpers.

## Render options

`renderPage()` accepts the same shared render options as `renderForm()`.

```twig
{{ craft.formie.renderPage(form, page, {
    themeConfig: {
        page: {
            attributes: {
                class: 'my-page',
            },
        },
    },
}) }}
```
