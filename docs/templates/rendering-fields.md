# Rendering Fields

Render fields manually when you want to control most of the form layout yourself.

This gives you a lot more freedom, but it also means you are taking on more responsibility for how the form behaves.

```twig
{% set form = craft.formie.forms.handle('contactForm').one() %}

{{ craft.formie.formAssets(form) }}

<form method="post" data-fui-form="{{ form.configJson }}">
    {{ csrfInput() }}
    {{ actionInput('formie/submissions/submit') }}
    {{ hiddenInput('handle', form.handle) }}

    {% for field in form.getFields() %}
        {{ craft.formie.renderField(form, field) }}
    {% endfor %}
</form>
```

## What changes when you do this

Once you render fields yourself, Formie is no longer responsible for the overall page structure.

That means you need to think about:

- your own layout wrappers
- where buttons belong
- where messages and errors should appear
- whether Formie's browser startup still has the data it needs to work

## Rendering a specific field

You can also render a field directly by handle when you only need one field from the form.

```twig
{{ craft.formie.renderField(form, 'firstName') }}
```

## Shared render options

`renderField()` accepts the same core render options, plus a few field-specific overrides when needed.

For example, you can still pass `themeConfig`, `fieldNamespace`, or `templateVars` here.

## A practical warning

Manual field rendering is not automatically better. It works best when you have a real layout need, not just because it feels more explicit.

If you mostly want different classes or wrappers, [Theme Config](/theming/theme-config) or [Template Overrides](/theming/template-overrides) are usually cheaper to maintain.
