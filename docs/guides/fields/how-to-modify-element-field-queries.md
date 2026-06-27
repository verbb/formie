# How to modify Element field's queries to change what elements can be chosen

An element field (Entries, Categories, Products, Tags, Users and Variants) all have one thing in common — they output a list of elements for users to select. This can be in the form of a Dropdown, or Radio Buttons field, enabling the user to select a single element, or a Multi-Dropdown or Checkboxes fields to allow multiple selections.

By default, Formie will render **all** elements that match the criteria you define in the field settings. So you might want to show all categories from a category group **Location** for the user to pick. That's much more efficient than writing out all the options again in a regular Dropdown/etc field.

But what if your **Location** category group contains hundreds of categories, and you don't want the user to be able to pick from all of them. Or maybe you have more complex logic, where you only want to include categories based on a custom field inside the category itself?

On its own, Formie won't let you define this logic. Instead, you'll want to modify the [Element Query](https://craftcms.com/docs/4.x/element-queries.html) for these fields. Much like you would in your regular Twig templates when making an entry or category query.

### Template Overrides
You can modify the element query used for fields using [template overrides](https://verbb.io/craft-plugins/formie/docs/theming/template-overrides). This encompasses creating a Twig template for the front-end of the field.

After you've followed the guide on setting up a Form Template, we'll assume you've created a folder to house your template overrides in `templates/_forms`.

Create new file `templates/_forms/fields/categories` with the following content:

```twig
{% set originalField = field %}

{# Override the `field` with the correct display type, populated from the Recipients field #}
{% set field = originalField.getDisplayTypeField() %}

{# Get the value for the field, depending on the display type #}
{% set value = originalField.getDisplayTypeValue(value) %}

{% if originalField.displayType == 'dropdown' %}
    {{ formieInclude('fields/dropdown') }}
{% endif %}

{% if originalField.displayType == 'checkboxes' %}
    {{ formieInclude('fields/checkboxes') }}
{% endif %}

{% if originalField.displayType == 'radio' %}
    {{ formieInclude('fields/radio') }}
{% endif %}
```

This is the default template for a categories field. Next, let's try modifying the element query. At the top of this file, let's modify the query with:

```twig
{% do field.setElementsQuery(elementsQuery.limit(2).orderBy('title')) %}

{% set originalField = field %}
...
```

When rendering this template, we'll have access to an `elementsQuery` variable which will be a `CategoryQuery`. For each element field type, this will of course be the corresponding query to the element in question. We can modify this query by adding more parameters to the object, as shown in the above example where we want to limit to only showing 2 items, and order by their title.

You could also add more complexity by querying on custom fields — or anything you could normally do for element queries.

```twig
{% do field.setElementsQuery(elementsQuery.location('australia').orderBy('city')) %}
```

And lastly, we call `field.setElementsQuery()` to apply this modified element query back to the field.
