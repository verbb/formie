# Populating Forms
When rendering a form, you might like to populate the values of a field with some default values.

```twig
{# Sets the field with handle `text` to "Some Value" for the form with a handle `contactForm` #}
{% do craft.formie.populateFormValues('contactForm', { text: "Some Value" }) %}

{# or use a `form` object #}
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}
{% do craft.formie.populateFormValues(form, { text: "Some Value" }) %}

{# Must be done before the `renderForm()` #}
{{ craft.formie.renderForm('contactForm') }}
```

### Element Fields
For some element fields (Entries, Categories, Tags, Users, Products, Variants), you'll need to supply the value of an element query, instead of just the ID of the element you want to populate. For example, for an Entries field:

```twig
{% do craft.formie.populateFormValues(form, {
    entriesField: craft.entries.id(123),
}) %}
```

Here, we're settings the Entries field (with a handle `entriesField`) to an Entry query. Note how we're _not_ using `.one()` here. This is because element fields require a query, not just an ID.

You can do the same for other elements:

```twig
{% do craft.formie.populateFormValues(form, {
    productsField: craft.products.id(6457),
}) %}
```