# Form Templates
Formie comes with all the required Twig templates to make your forms look great. Additionally, we provide CSS and JS outputted alongside to form to ensure you can use forms out-of-the-box with no configuration. Read more about [Form Templates](docs:feature-tour/form-templates).

# Custom Templates
While Formie's default templates suit most needs, you can of course roll your own templates, so you have total control over the form, field, layout and more.

:::warning
By overriding template files, you will no longer receive bug fixes and improvements. For more information on how to customize templates without overriding template files, please refer to the [hooks documentation](docs:developers/hooks).
:::

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
Check out the raw templates on [Formie's Github](https://github.com/verbb/formie/tree/craft-3/src/templates/_special) - they'll be the most up to date.
:::

## Overriding Form Templates
To override the form template, provide a file named `form.html`.

### Available Template Variables
Field templates have access to the following variables:

Variable | Description
--- | ---
`form` | A [Form](docs:developers/form) object, for the form instance this template is for.
`options` | A collection of additional options.
`submission` | The current [Submission](docs:developers/submission) object this this form may or may not have.

## Overriding Page Templates
To override the page template, provide a file named `page.html`.

### Available Template Variables
Field templates have access to the following variables:

Variable | Description
--- | ---
`form` | A [Form](docs:developers/form) object that this field belongs to.
`page` | A [Page](docs:developers/page) object, for the page instance this template is for.
`options` | A collection of additional options.

## Overriding Field Wrapper Templates
To override the field template, provide a file named `field.html`. This is the wrapper template around all fields. You can also override individual field types' templates, rather than changing the template for every field, regardless of type.

### Available Template Variables
Field templates have access to the following variables:

Variable | Description
--- | ---
`form` | A [Form](docs:developers/form) object that this field belongs to.
`field` | A [Field](docs:developers/field) object, for the field instance this template is for.
`handle` | The handle of the field.
`options` | A collection of additional options, available for some fields.
`element` | The current [Submission](docs:developers/submission) object this this form may or may not have.

## Overriding Field Templates
You'll notice the above structure includes the `fields/` directory. Inside this directory are a mixture of folder and individual files, each representing a template that you're able to override.

First, you'll need to identify the template's name. It's derived from the PHP class name for the field, converted to a "kebab" string. For easy reference, you can use the below table.

Class Name | Template
--- | ---
`Address` | `address.html`
`Agree` | `agree.html`
`Categories` | `categories.html`
`Checkboxes` | `checkboxes.html`
`Date` | `date.html`
`Dropdown` | `dropdown.html`
`Email` | `email.html`
`Entries` | `entries.html`
`FileUpload` | `file-upload.html`
`Group` | `group.html`
`Heading` | `heading.html`
`Hidden` | `hidden.html`
`Html` | `html.html`
`MultiLineText` | `multi-line-text.html`
`Name` | `name.html`
`Number` | `number.html`
`Phone` | `phone.html`
`Products` | `products.html`
`Radio` | `radio.html`
`Repeater` | `repeater.html`
`Section` | `section.html`
`SingleLineText` | `single-line-text.html`
`Table` | `table.html`
`Tags` | `tags.html`
`Users` | `users.html`
`Variants` | `variants.html`

Adding a template file in your specified template directory will use that template file over the ones Formie provide.

You might also have noticed we've shown `address` in a folder. Due to how Twig resolves templates, the below are equivalent:

```
fields/address.html - Is the same as - fields/address/index.html
```

For complex fields that have multiple templates, we've used folders to organise multiple templates in a single folder. You're welcome to follow this same pattern, but you're not forced to.

For example, the Address field, has the following templates in a folder:

- `fields/address/_country.html`
- `fields/address/_field.html`
- `fields/address/_input.html`
- `fields/address/index.html`

This is because the address field has many parts, and is complex. If you want to override the templates for this field, you just need to alter the `index.html` file. You can use the includes (denoted by `_`), or you don't have to.

## CSS & JS
Some field contain JS related to their specific field type. This is included in the field template. For instance, the Repeater field contains JS to allow adding and removing of repeater elements. If you override the field templates for fields that contain JS, you'll need to either include this, or come up with your own solution.

For example, the repeater field contains the following in its template:

```twig
{% set jsFile = view.getAssetManager().getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/fields/repeater.js', true) %}
{% do view.registerJsFile(jsFile) %}

{% js %}
    new FormieRepeater({{ { formId: form.id } | json_encode | raw }});
{% endjs %}
``` 

Here, we're including a `repeater.js` file, which contains the logic to handling adding or removing items. You can choose to include this in your custom template, but know that you'll need to ensure your classes and other elements match up with the JS. Or choose to omit this JS and handle the required functionality yourself.
