# Rendering Fields

Render fields manually when you want to control most of the form layout yourself.

This gives you a lot more freedom, but it also means you are taking on more responsibility for how the form behaves.

Start with the complete form example in [Custom Rendering](/theming/custom-rendering), including its browser attributes, hidden inputs and enabled spam protections. Inside that form, render the fields and your submit button:

```twig
{% for field in form.getFields() %}
    {{ craft.formie.renderField(form, field) }}
{% endfor %}
<button type="submit" data-submit-action="submit">Submit</button>
```

This fragment supplies only fields and a button. The surrounding form setup is required for Formie submission handling. For a form that posts to `users/login` or another Craft action, follow [Craft Forms](/templates/craft-forms).

## What Changes When You Do This

Once you render fields yourself, Formie is no longer responsible for the overall page structure.

That means you need to think about:

- your own layout wrappers
- where buttons belong
- where messages and errors should appear
- whether Formie's browser startup still has the data it needs to work

## Rendering a Specific Field

You can also render a field directly by handle when you only need one field from the form.

```twig
{{ craft.formie.renderField(form, 'firstName') }}
```

## Shared Render Options

`renderField()` accepts the same core render options, plus a few field-specific overrides when needed.

For example, you can still pass `themeConfig`, `fieldNamespace`, or `templateVars` here.

## A Practical Warning

Manual field rendering is not automatically better. It works best when you have a real layout need, not just because it feels more explicit.

If you mostly want different classes or wrappers, [Theme Config](/theming/theme-config) or [Template Overrides](/theming/template-overrides) are usually cheaper to maintain.
