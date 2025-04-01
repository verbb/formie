# Rendering Pages
In some cases, you may wish to render specific pages, rather than an entire form. Pages will also include the submit button and captchas. For multi-page forms, this can be multiple buttons for going back to a previous page, or to continue to the next page.

Rendering a page can be achieved with the following Twig.

```twig
{% set form = craft.formie.forms.handle('contactUs').one() %}

{% for page in form.getPages() %}
    {{ craft.formie.renderPage(form, page) }}
{% endfor %}
```

Here, we fetch the [Form](docs:developers/form) object, call `getPages()` to fetch all the pages the form has, and loop through them. Even for single-page forms, `getPages()` will return an array of [Page](docs:developers/page) objects.

This may be particularly useful if you want control over the `<form>` element of a form, rather than rely on `craft.formie.renderForm()`.

```twig
{% set form = craft.formie.forms.handle('contactUs').one() %}

<form method="post" data-fui-form="{{ form.configJson }}">
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

- The `data-fui-form` attribute are required, and Formie's JavaScript relies on this to initialise the form.
- Some additional Twig content in the `<form>` element, such as `csrfInput()`, `actionInput()`. This is to ensure Formie can process the content of the form and create a submission from it.

:::tip
Make sure to include the `data-fui-form` attribute with JSON configuration from the form. Without this attribute, Formie's JavaScript will fail to initialise, meaning client-side validation, captchas and more will not work.
:::

## CSS & JS
You'll probably notice your form is looking a little plain and some aspects of it won't work like validation, captchas and more. That's because unlike calling `craft.formie.renderForm()`, rendering just form pages won't automatically add Formie's CSS or JavaScript to the webpage for you.

To address this, we just need to include a call to ensure Formie's CSS and JS is added to the webpage.

```twig
{% set form = craft.formie.forms.handle('contactUs').one() %}

{% do craft.formie.registerAssets(form) %}

<form method="post" data-fui-form="{{ form.configJson }}">
```

This will respect the [Form Template](docs:feature-tour/form-templates) setting you may have on your form, which controls where to render the CSS and JS.

It's important to include this `registerAssets` before the `<form>` rendering tag. You could also include them separately as below:

```twig
{% set form = craft.formie.forms.handle('contactUs').one() %}

{% do craft.formie.renderFormCss(form) %}
{% do craft.formie.renderFormJs(form) %}

<form method="post" data-fui-form="{{ form.configJson }}">
```

Read further on the [`registerAssets()`](docs:template-guides/available-variables#craft-formie-registerAssets), [`renderFormCss()`](docs:template-guides/available-variables#craft-formie-renderFormCss) and [`renderFormJs()`](docs:template-guides/available-variables#craft-formie-renderFormJs) functions.


## Render Options
A second argument to `renderPage()` allows you to pass in variables used as [Render Options](docs:theming/render-options).

```twig
{% set renderOptions = {
    someOption: 'someValue',
} %}

{% set form = craft.formie.forms.handle('contactUs').one() %}

{% for page in form.getPages() %}
    {{ craft.formie.renderPage(form, page, renderOptions) }}
{% endfor %}
```

## Override Page Settings
You can also dynamically override any settings for the page using `setPageSettings()`. You'll need to use the "handle" of the page, which is automatically derived from the Page Title as a kebab-delimited string. So if your page has the label "Contact Details" the handle would be `contact-details`.

```twig
{% set form = craft.formie.forms.handle('contactForm').one() %}

{% do form.setPageSettings('page-1', {
    {# Override the submit button text for "Page 1" #}
    submitButtonLabel: 'Click here to submit',
}) %}

{{ craft.formie.renderForm(form) }}
```

Check out the [Page](docs:developers/page) object for all available settings.

## Rendering Rows & Fields
You can render rows for a page, rather than relying on the render function to output them. In this instance, you would want to [Render the Field](docs:template-guides/rendering-fields), rather than the entire page.

```twig
{% set form = craft.formie.forms.handle('contactUs').one() %}

<form method="post" data-fui-form="{{ form.configJson }}">
    {{ csrfInput() }}
    {{ actionInput('formie/submissions/submit') }}
    {{ hiddenInput('handle', form.handle) }}

    {% for page in form.getPages() %}
        <div class="page" data-page-id="{{ page.id }}">
            {% for row in page.getRows() %}
                <div class="row">
                    {% for field in row.fields %}
                        <div class="col">
                            {{ craft.formie.renderField(form, field) }}
                        </div>
                    {% endfor %}
               </div>
            {% endfor %}
       </div>
    {% endfor %}
</form>
```

## Getting Pages
As shown above, we can fetch all available pages on a form using `form.getPages()`. 

```twig
{% set form = craft.formie.forms.handle('contactUs').one() %}

{% for page in form.getPages() %}
    {{ page.name }}
{% endfor %}
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
