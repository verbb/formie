# How to modify Element field queries to change what elements can be chosen

Element fields — Entries, Categories, Users, Tags, Products, and Variants — all work the same way underneath: Formie builds an element query from the field settings, then renders that list as a dropdown, radio buttons, checkboxes, or multi-select. That is much more efficient than copying hundreds of options into a static Dropdown field.

Sometimes the field settings alone are not enough. You might have hundreds of categories but only want a filtered subset, or you need to query on a custom field inside the element. In those cases, modify the element query in a template override — the same way you would in a regular Craft template.

## Prerequisites

- [Entries field](/fields/entries), [Categories field](/fields/categories), or [Users field](/fields/users) on a form
- A [Form Template](/templates/form-templates) with a folder for field overrides (for example `templates/_forms`)

## Set up a template override

After you have a Form Template configured, create an override for the element field type you want to change. The example below uses Categories; the same pattern applies to Entries, Users, and the other element field types.

Create `templates/_forms/fields/categories.html`:

```twig
{% set originalField = field %}

{# Populate the value so query logic can depend on current submission state if needed #}
{% do originalField.populateValue(value, submission) %}

{# Override the `field` with the correct display type #}
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

This mirrors Formie's default Categories template. The important part is that `elementsQuery` is available in this template — it is the live query object Formie built from your field settings.

## Modify the query

Add your query changes before the display-type logic. Call `setElementsQuery()` on the **original** element field so the modified query is used when options are resolved:

```twig
{% do originalField.setElementsQuery(
    elementsQuery
        .limit(2)
        .orderBy('title')
) %}

{% set originalField = field %}
...
```

When the form renders, only the first two matching categories (ordered by title) appear as choices.

### Filter on custom fields

Element queries support the same parameters you use elsewhere in Craft:

```twig
{% do originalField.setElementsQuery(
    elementsQuery
        .location('australia')
        .orderBy('city')
) %}
```

You can chain any valid query method — related elements, custom field values, date ranges, and so on.

### Filter based on page context

Because this runs in Twig at render time, you can use the current entry, logged-in user, or request parameters:

```twig
{% set currentEntry = craft.app.request.getSegment(2) 
    ? craft.entries.slug(craft.app.request.getSegment(2)).one() 
    : null %}

{% if currentEntry %}
    {% do originalField.setElementsQuery(
        elementsQuery.relatedTo(currentEntry)
    ) %}
{% endif %}
```

## Apply the override to your form

Assign your Form Template to the form in the form builder (**Settings → Form Template**), or pass it when rendering:

```twig
{{ craft.formie.renderForm('contactForm', {
    theme: '_forms',
}) }}
```

The exact theme/template key depends on how your Form Template is registered. See [Form Templates](/templates/form-templates) for setup details.

## GraphQL and headless forms

Template overrides only run when Formie renders through Twig. For GraphQL or client-rendered forms, element field options come from the server-side query configured on the field. If you need dynamic filtering in headless flows, use a [Custom Provider](/developers/option-source-providers) or listen for the `modifyElementQuery` event in PHP:

```php
use verbb\formie\events\ModifyElementFieldQueryEvent;
use verbb\formie\base\ElementField;
use yii\base\Event;

Event::on(ElementField::class, ElementField::EVENT_MODIFY_ELEMENT_QUERY, function(ModifyElementFieldQueryEvent $event) {
    if ($event->field->handle !== 'myCategories') {
        return;
    }

    $event->query->limit(10);
});
```

## Related

- [Entries field](/fields/entries)
- [Categories field](/fields/categories)
- [Users field](/fields/users)
- [Form Templates](/templates/form-templates)
