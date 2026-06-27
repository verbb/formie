# Custom templating for Repeater fields

Repeater fields let users add and remove rows of nested fields. Most projects can style them with [theme config](/theming/theme-config#repeater-field), but when you need different markup — a card layout, a custom add/remove control, or tighter integration with your design system — template overrides are the right tool.

## Prerequisites

- A form with at least one [Repeater](/fields/repeater) field
- A [Form Template](/theming/template-overrides) with **Use Custom Template** enabled (for example `_forms`)
- Familiarity with [Rendering Fields](/templates/rendering-fields)

## Start with theme config

Before overriding templates, check whether theme config already solves the problem. Repeater exposes tags such as `nestedFieldRow`, `fieldAddButton`, and `fieldRemoveButton`:

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        repeater: {
            nestedFieldRow: {
                attributes: {
                    class: 'rounded-lg border p-4 mb-4',
                },
            },
            fieldAddButton: {
                attributes: {
                    class: 'btn btn-secondary',
                },
            },
        },
    },
}) }}
```

Use a template override when the row structure itself needs to change, not just classes or attributes.

## How Repeater values work

Each Repeater row is a structured group of nested field values stored on the submission. In Twig, the field's `value` is an array of rows. Formie's front-end JavaScript manages row indexing, add/remove actions, and minimum row counts.

When you override Repeater templates, you must preserve the data attributes the browser package expects. See the [Repeater UI reference](https://github.com/verbb/formie/blob/craft-5/packages/docs/browser/ui-reference/fields/repeater.md) for the full list — the important ones are:

- `data-formie-repeater-container` on the live row container
- `data-formie-repeater-item` on each row
- `data-formie-repeater-add` and `data-formie-repeater-remove` on the buttons
- A `<script type="text/x-template">` with `data-formie-repeater-template` and `__ROW__` placeholders for new rows

## Set up your override directory

Create a Form Template in **Formie → Settings → Form Templates**, enable **Use Custom Template**, and set the path to your override directory (for example `_forms`). Use **Copy Templates** to seed Formie's defaults, then edit the Repeater partials:

```text
templates/_forms/fields/repeater/index.html
templates/_forms/fields/repeater/_row.html
```

Assign that Form Template to the form you are customising.

## Override the Repeater index template

The index template renders the row container, existing rows, the add button, and the row template used when adding new rows. This example follows Formie's default structure using `fieldtag()` and `formieInclude()`:

```twig
{# templates/_forms/fields/repeater/index.html #}

{{ hiddenInput(field.getHtmlName(), '') }}

{% set templateId = "#{field.getHtmlId(form)}-template" %}

{% fieldtag 'nestedFieldContainer' %}
    {% if value %}
        {% for block in value %}
            {{ formieInclude('fields/repeater/_row', {
                index: loop.index0,
            }) }}
        {% endfor %}
    {% endif %}
{% endfieldtag %}

{{ fieldtag('fieldAddButton') }}

{% if includeScriptsInline %}
    <script id="{{ templateId }}" type="text/x-template" data-formie-template-id="{{ templateId }}" data-repeater-template="{{ field.handle }}">{% apply spaceless %}
        {{ formieInclude('fields/repeater/_row', {
            index: '__ROW__',
        }) }}
    {% endapply %}</script>
{% else %}
    {% script with { id: templateId, type: 'text/x-template', 'data-formie-template-id': templateId, 'data-repeater-template': field.handle } %}{% apply spaceless %}
        {{ formieInclude('fields/repeater/_row', {
            index: '__ROW__',
        }) }}
    {% endapply %}{% endscript %}
{% endif %}
```

The `{% script %}` tag (or inline `<script>` for GraphQL rendering) holds the template for dynamically added rows. The `__ROW__` placeholder is replaced with the next row index when a user clicks add.

## Override the row template

Each row loops nested fields and renders them with `craft.formie.renderField()`:

```twig
{# templates/_forms/fields/repeater/_row.html #}

{% fieldtag 'nestedField' %}
    {% fieldtag 'nestedFieldWrapper' %}
        {% fieldtag 'nestedFieldRows' %}
            {% for row in field.getRows(true, index) %}
                {% fieldtag 'nestedFieldRow' %}
                    {% for field in row.getFields() %}
                        {{ craft.formie.renderField(form, field) }}
                    {% endfor %}
                {% endfieldtag %}
            {% endfor %}
        {% endfieldtag %}

        {{ fieldtag('fieldRemoveButton') }}
    {% endfieldtag %}
{% endfieldtag %}
```

Formie handles input namespacing for nested fields automatically when you render through `renderField()`. You should not need to call `setParentField()` manually.

## Customise from here

Once the override is in place, you can change wrappers, reorder nested fields, or add your own classes through `fieldtag()` overrides in the template. Keep the Repeater data attributes and the add/remove button slots intact so Formie's JavaScript continues to manage rows.

If you only need different classes on existing elements, prefer [theme config](/theming/theme-config#repeater-field) — it survives Formie updates with less maintenance.

## Related

- [Repeater field](/fields/repeater)
- [Rendering Fields](/templates/rendering-fields)
- [Template Overrides](/theming/template-overrides)
- [Theme config — Repeater](/theming/theme-config#repeater-field)
