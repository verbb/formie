# Custom templating for Repeater fields

A Repeater field is — you guessed it — for repeatable content. It allows you to define the inner, nested field of any type and groups them into rows. Each row can be created or deleted as many times as you require.

If you've ever used a Repeater field, you might realise how similar it is to a Matrix or [Super Table](https://github.com/verbb/super-table) field, and you'd be right! The Repeater field is remarkably similar to both — although closer to Super Table due to the lack of a "blocktype" concept that Matrix does.

### Understanding a Repeater
Before we dive into templating, let's take a moment to explain how a Repeater field works. If you're familiar with the inner workings of Matrix and Super Table fields, this should be quick. Even more so, if you've has experience templating Matrix and Super Table fields you'll be right at home.

Each repeatable row in a Repeater field is its own element. As such, any content saved to a Repeater field inside a Submission is actually stored on the Repeater row element. This is exactly how Matrix and Super Table fields work, and something to keep in mind as we proceed.

### Templating
So with that background context in mind, let's get stuck into templating. We're going to use [Template Overrides](https://verbb.io/craft-plugins/formie/docs/theming/template-overrides) for this example, and we won't cover setting up your form template in this guide.

We'll assume your overrides sit in `/templates/_forms`, so go ahead and create the following files.

:::tip
We're going to forego Theme Config in this example, purely for simplicity, so everything will be HTML elements. As such, the field and contents will be un-styled.
:::

```twig
/templates/_forms/fields/repeater/index.html

{{ hiddenInput(field.getHtmlName(), '') }}

<div data-repeater-rows>
    {% if value and value.exists() %}
        {% for block in value.all() %}
            {{ formieInclude('fields/repeater/_row', {
                id: block.id ?? 'new' ~ loop.index,
            }) }}
        {% endfor %}
    {% elseif field.minRows > 0 %}
        {% for i in 1..field.minRows %}
            {{ formieInclude('fields/repeater/_row', {
                id: 'new' ~ i,
            }) }}
        {% endfor %}
    {% endif %}
</div>

<button data-add-repeater-row="{{ field.handle }}">{{ field.addLabel }}</button>

{% set includeScriptsInline = renderOptions.includeScriptsInline ?? false %}

{% if includeScriptsInline %}
    {# For GraphQL requests we need to render this inline #}
    <script type="text/x-template" data-repeater-template="{{ field.handle }}">
        {{ formieInclude('fields/repeater/_row', {
            id: '__ROW__',
        }) }}
    </script>
{% else %}
    {# Have to use the `script` tag here to place the script outside of a Vue3 wrapper #}
    {# as Vue3 will strip out inline `script` tags (all other scenarios would be fine) #}
    {% script with { type: 'text/x-template', 'data-repeater-template': field.handle } %}
        {{ formieInclude('fields/repeater/_row', {
            id: '__ROW__',
        }) }}
    {% endscript %}
{% endif %}
```

The only real advanced bit here is the multiple handling of the individual row's template. We need special handling (as the comments show) for some cases. Otherwise, the `<script>` tag is a JS template that houses our template for an individual row, which is up next.

```twig
/templates/_forms/fields/repeater/_row.html

{# Keep track of the outer repeater field #}
{% set repeaterField = field %}

<div data-repeater-row>
    {# There will only ever be 1 page. #}
    {% set page = repeaterField.fieldLayout.pages[0] ?? null %}
    
    {% if page %}
        {% for row in page.rows %}
            {% for field in row.fields %}
                {# Tell child fields about this parent, so that namespacing works #}
                {% do field.setParentField(repeaterField, "rows[#{id}][fields]") %}

                {{ craft.formie.renderField(form, field, {
                    element: block ?? null,
                }) }}
            {% endfor %}
        {% endfor %}
    {% endif %}

    <button data-remove-repeater-row="{{ repeaterField.handle }}">Remove</button>

    {{ hiddenInput(field.getHtmlName('sortOrder[]'), id) }}
</div>
```

We fetch the field layout for the Repeater field and get its first (and only) page. We then loop through all rows and fields and render each field individually.

The most important thing about the templating is to ensure the namespace (the values used in the `name` attributes of inputs) is correct, as this is what's sent to the server.

To illustrate, here's what the inputs of two rows in a Repeater field would look like:

```twig
<input type="hidden" name="fields[repeaterField][rows][new1][fields][plainText]">
<input type="hidden" name="fields[repeaterField][sortOrder][]" value="new1">

<input type="hidden" name="fields[repeaterField][rows][new2][fields][plainText]">
<input type="hidden" name="fields[repeaterField][sortOrder][]" value="new2">
```

You might also have noticed a few `data-` attributes. These are all required and are what Formie's JS hooks into to facilitate adding/removing new rows. The rest of the HTML including classes is entirely up to you.

With this all in place, you should have a custom, working example for your Repeater field.
