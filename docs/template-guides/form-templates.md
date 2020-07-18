# Form Templates
Formie comes with all the required Twig templates to make your forms look great. Additionally, we provide CSS and JS outputted alongside to form to ensure you can use forms out-of-the-box with no configuration. Read more about [Form Templates]().

# Custom Templates
While Formie's default templates suit most needs, you can of course roll your own templates, so you have total control over the form, field, layout and more.

The great thing about Formie's custom templates is that it doesn't have to be all-or-nothing. You can choose to override a single template, or all. For instance, you might have very specific markup needs to a Select field. You can override just the template for the select field, and nothing else.

To get started, it's worth taking the time to understand the structure of how templates go together.

:::tip
We're using the `.html` extension here for clarity. You can use `.twig` or whatever you have set in your [defaultTemplateExtensions](https://docs.craftcms.com/v3/config/config-settings.html#defaulttemplateextensions) for the actual files.
:::

- `form.html`
- `field.html`
- `page.html`
- `fields/`
    - `address/`
        - `country.html`
        - `...`
    - `agree.html`
    - `categories.html`
    - `...`

Let's start with the top-level templates.

:::tip
Check out the raw templates on [Formie's Github]() - they'll be the most up to date.
:::

## Overriding Form Templates
To override the form template, provide a file named `form.html`.

## Overriding Page Templates
To override the page template, provide a file named `page.html`.

## Overriding Field Templates
To override the field template, provide a file named `field.html`. This is the wrapper template around all fields. You can also override individual field types' templates, rather than changing the template for every field, regardless of type.

You'll notice in the above structure the `fields/` directory. Inside this directory are a mixture of folder and individual files. Folders are mostly reserved for complex fields that have multiple templates. Your custom templates must mimic this structure. You do not need to include the individual includes.

For example, if you want to override the Address field, which has the following templates:

- `fields/address/_country.html`
- `fields/address/_field.html`
- `fields/address/_input.html`
- `fields/address/index.html`

This is because the address field has many parts, and is complex. If you want to override the templates for this field, you just need to alter the `index.html` file. You can use the includes (denoted by `_`), or you don't have to.

For other templates like the Agree field, replacing `fields/agree.html` is all that's required.

## CSS & JS
Some field contain JS related to their specific field type. This is included in the field template. For instance, the Repeater field contains JS to allow adding and removing of repeater elements. If you override the field templates for fields that contain JS, you'll need to either include this, or come up with your own solution.

For example, the repeater field contains the following in its template:

``twig
{% set jsFile = view.getAssetManager().getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/fields/repeater.js', true) %}
{% do view.registerJsFile(jsFile) %}

{% js %}
    new FormieRepeater({{ { formId: form.id } | json_encode | raw }});
{% endjs %}
``` 

Here, we're including a `repeater.js` file, which contains the logic to handling adding or removing items. You can choose to include this in your custom template, but know that you'll need to ensure your classes and other elements match up with the JS. Or choose to omit this JS and handle the required functionality yourself.
