# Rendering Pages
In some cases, you may wish to render specific pages, rather than an entire form. Pages will also include the submit button and captchas. For multi-page forms, this can be multiple buttons for going back to a previous page, or to continue to the next page.

Rendering a page can be acheived with the following Twig.

```twig
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}

{% for page in form.getPages() %}
    {{ craft.formie.renderPage(form, page) }}
{% endif %}
```

Here, we fetch the [Form]() object, call `getPages()` to fetch all the pages the form has, and loop through them. Even for single-page forms, `getPages()` will return an array of [Page]() objects.

This may be particularly useful if you want control over the `<form>` element of a form, rather than rely on `craft.formie.renderForm()`.

```twig
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}

<form id="my-form" method="post">
    {{ csrfInput() }}
    {{ actionInput('formie/submissions/submit') }}
    {{ hiddenInput('handle', form.handle) }}

    <h2>Please fill out the form below</h2>

    {% for page in form.getPages() %}
        {{ craft.formie.renderPage(form, page) }}
    {% endfor %}
</form>
```

Note the use of some additional Twig content in the `<form>` element. This is to ensure Formie can process the content of the form and create a submission from it.

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
You can render rows for a page, rather than relying on the render function to output them. In this instance, you would want to [Render the Field](), rather than the entire page.

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

# Getting Pages
As shown above, we can fetch all available pages on a form using `form.getPages()`. 

```twig
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}

{% for page in form.getPages() %}
    {{ page.name }}
{% endif %}
```

Because pages don't have a unique handle to fetch by, you need to rely on this function to fetch all the pages for a form. If you want to find a specific page to use in your templates, you can either rely on the `id`, the `name` or the `sortOrder` of a [Page]() object.

You can also use some additional functions for multi-page forms:

```twig
{% set currentPage = form.getCurrentPage() %}
{% set nextPage = form.getNextPage() %}
{% set prevPage = form.getPreviousPage() %}
```

Each function will return a [Page]() object. When there aren't next or previous pages, `null` will be returned.

For more fine-grained control over rendering of a form, see [Rendering Fields]()
