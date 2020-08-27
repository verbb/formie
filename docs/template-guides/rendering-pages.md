# Rendering Pages
In some cases, you may wish to render specific pages, rather than an entire form. Pages will also include the submit button and captchas. For multi-page forms, this can be multiple buttons for going back to a previous page, or to continue to the next page.

Rendering a page can be acheived with the following Twig.

```twig
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}

{% for page in form.getPages() %}
    {{ craft.formie.renderPage(form, page) }}
{% endif %}
```

Here, we fetch the [Form](docs:developers/form) object, call `getPages()` to fetch all the pages the form has, and loop through them. Even for single-page forms, `getPages()` will return an array of [Page](docs:developers/page) objects.

This may be particularly useful if you want control over the `<form>` element of a form, rather than rely on `craft.formie.renderForm()`.

```twig
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}

<form id="{{ form.formId }}" method="post" data-config="{{ form.configJson }}">
    {{ csrfInput() }}
    {{ actionInput('formie/submissions/submit') }}
    {{ hiddenInput('handle', form.handle) }}

    <h2>Please fill out the form below</h2>

    {% for page in form.getPages() %}
        {{ craft.formie.renderPage(form, page) }}
    {% endfor %}
</form>
```

Let's run through a few things of note:

- The `id` attribute is required, and Formie's JavaScript relies on this to initialise this form.
- The `data-config` attribute is required, and Formie's JavaScript relies on this to initialise this form.
- Some additional Twig content in the `<form>` element, such as `csrfInput()`, `actionInput()`. This is to ensure Formie can process the content of the form and create a submission from it.

:::tip
Make sure to use `{{ form.formId }}` for the `id` attribute, and `{{ form.configJson }}` for the `data-config` attribute. These are the only two things Formie needs to hook up the JavaScript used to handle forms, and are required if you're writing the `<form>` element in your templates.
:::

If you are using custom templates, you can also pass in a number of options to the rendering function. These don't have any effect on the default templates, but provide a means to pass additional data to your templates.

```twig
{% set options = {
    someOption: 'someValue',
} %}

{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}

{% for page in form.getPages() %}
    {{ craft.formie.renderPage(form, page, options) }}
{% endif %}
```

# Rendering Rows & Fields
You can render rows for a page, rather than relying on the render function to output them. In this instance, you would want to [Render the Field](docs:template-guides/rendering-fields), rather than the entire page.

```twig
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}

<form id="{{ form.formId }}" method="post" data-config="{{ form.configJson }}">
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

# Getting Pages
As shown above, we can fetch all available pages on a form using `form.getPages()`. 

```twig
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}

{% for page in form.getPages() %}
    {{ page.name }}
{% endif %}
```

Because pages don't have a unique handle to fetch by, you need to rely on this function to fetch all the pages for a form. If you want to find a specific page to use in your templates, you can either rely on the `id`, the `name` or the `sortOrder` of a [Page](docs:developers/page) object.

You can also use some additional functions for multi-page forms:

```twig
{% set currentPage = form.getCurrentPage() %}
{% set nextPage = form.getNextPage() %}
{% set prevPage = form.getPreviousPage() %}
```

Each function will return a [Page](docs:developers/page) object. When there aren't next or previous pages, `null` will be returned.

For more fine-grained control over rendering of a form, see [Rendering Fields](docs:template-guides/rendering-fields)
