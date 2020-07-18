# Rendering Fields
You can render a specific field completely on its own, outside of a `<form>` element context. This can be useful if you want complete control over your form layout. Due to this, you are required to provide your own template code for the `<form>` element, in order for submissions to actually work.

The `renderField()` reqiures both a [Form]() object and a [Field]() object.

```twig
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}

<form id="my-form" method="post">
    {{ csrfInput() }}
    {{ actionInput('formie/submissions/submit') }}
    {{ hiddenInput('handle', form.handle) }}

    {% for field in form.getFields() %}
        {{ craft.formie.renderField(form, field) }}
    {% endfor %}
</form>
```

Note the use of some additional Twig content in the `<form>` element. This is to ensure Formie can process the content of the form and create a submission from it.

You can also use the handle of the field, for direct-access to the field you require.

```twig
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}

<form id="my-form" method="post">
    {{ csrfInput() }}
    {{ actionInput('formie/submissions/submit') }}
    {{ hiddenInput('handle', form.handle) }}

    {# Render the field with the handle `firstName` #}
    {{ craft.formie.renderField(form, 'firstName') }}
</form>
```

If you are using custom templates, you can also pass in a number of options to the rendering function. These don't have any effect on the default templates, but provide a means to pass additional data to your templates.

```twig
{% set options = {
    someOption: 'someValue',
} %}

{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}

{% for field in form.getFields() %}
    {{ craft.formie.renderField(form, field, options) }}
{% endfor %}
```

# Rendering Layout
You can also render fields in the layout you build in the form builder, with pages, rows and columns. To do this, we'll need to loop through each page, loop through each row, then finally loop through each field.

```twig
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}

<form id="my-form" method="post">
    {{ csrfInput() }}
    {{ actionInput('formie/submissions/submit') }}
    {{ hiddenInput('handle', form.handle) }}

    {% for page in form.getPages() %}
        <div class="page" data-page-id="{{ page.id }}">
            {% for row in page.getRows() %}
                <div class="row">
                    {% for field in row.getFields() %}
                        <div class="col" data-col="{{ field.columnWidth }}">
                            {{ craft.formie.renderField(form, field) }}
                        </div>
                    {% endfor %}
               </div>
            {% endfor %}
       </div>
    {% endfor %}
</form>
```

Here we have a completely custom layout, with Formie handling the rendering of the field. For more information on what properties are available, consult the [Page](), [Row]() and [Field]() docs.
