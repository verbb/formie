# Element Field

Formie includes an element field you can add to other Craft elements so editors can choose a form.

That lets an entry, category, or other element decide which form should appear alongside its content.

The field returns a Form query, so in Twig you will usually call `.one()` before rendering.

```twig
{% set form = entry.myFormFieldHandle.one() %}

{% if form %}
    {{ craft.formie.renderForm(form) }}
{% endif %}
```
