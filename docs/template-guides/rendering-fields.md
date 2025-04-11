# Rendering Fields
You can render a specific field completely on its own, outside a `<form>` element context. This can be useful if you want complete control over your form layout. Due to this, you are required to provide your own template code for the `<form>` element, in order for submissions to actually work.

The `renderField()` requires both a [Form](docs:developers/form) object and a [Field](docs:developers/field) object.

```twig
{% set form = craft.formie.forms.handle('contactUs').one() %}

<form method="post" data-fui-form="{{ form.configJson }}">
    {{ csrfInput() }}
    {{ actionInput('formie/submissions/submit') }}
    {{ hiddenInput('handle', form.handle) }}

    {% for field in form.getFields() %}
        {{ craft.formie.renderField(form, field) }}
    {% endfor %}
</form>
```

Let's run through a few things of note:

- The `data-fui-form` attribute are required, and Formie's JavaScript relies on this to initialise the form.
- Some additional Twig content in the `<form>` element, such as `csrfInput()`, `actionInput()`. This is to ensure Formie can process the content of the form and create a submission from it.

:::tip
Make sure to include the `data-fui-form` attribute with JSON configuration from the form. Without this attribute, Formie's JavaScript will fail to initialise, meaning client-side validation, captchas and more will not work.
:::

You can also use the handle of the field, for direct-access to the field you require.

```twig
{% set form = craft.formie.forms.handle('contactUs').one() %}

<form method="post" data-fui-form="{{ form.configJson }}">
    {{ csrfInput() }}
    {{ actionInput('formie/submissions/submit') }}
    {{ hiddenInput('handle', form.handle) }}

    {# Render the field with the handle `firstName` #}
    {{ craft.formie.renderField(form, 'firstName') }}
</form>
```

## CSS & JS
You'll probably notice your form is looking a little plain and some aspects of it won't work like validation, captchas and more. That's because unlike calling `craft.formie.renderForm()`, rendering just form fields won't automatically add Formie's CSS or JavaScript to the webpage for you.

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
A second argument to `renderField()` allows you to pass in variables used as [Render Options](docs:theming/render-options).

```twig
{% set renderOptions = {
    someOption: 'someValue',
} %}

{% set form = craft.formie.forms.handle('contactUs').one() %}

{% for field in form.getFields() %}
    {{ craft.formie.renderField(form, field, renderOptions) }}
{% endfor %}
```

## Rendering Layout
The previous examples have just covered rendering fields in a form in a simple manner. However you can render fields in the layout you build in the form builder, with pages, rows and columns. To do this, we'll need to loop through each page, loop through each row, then finally loop through each field.

```twig
{% set form = craft.formie.forms.handle('contactUs').one() %}

<form method="post" data-fui-form="{{ form.configJson }}">
    {{ csrfInput() }}
    {{ actionInput('formie/submissions/submit') }}
    {{ hiddenInput('handle', form.handle) }}

    {% for page in form.getPages() %}
        <div class="fui-page" data-id="{{ page.id }}">
            {% for row in page.getRows() %}
                <div class="fui-row fui-page-row">
                    {% for field in row.fields %}
                        {{ craft.formie.renderField(form, field) }}
                    {% endfor %}
               </div>
            {% endfor %}
       </div>
    {% endfor %}
</form>
```

Here we have a completely custom layout, with Formie handling the rendering of the field. For more information on what properties are available, consult the [Page](docs:developers/page), [Row](docs:developers/row) and [Field](docs:developers/field) docs.

## Override Field Settings
You can also dynamically override any settings for the field using `setFieldSettings()`.

```twig
{% set form = craft.formie.forms.handle('contactUs').one() %}

{% do form.setFieldSettings('plainText', {
    {# Override the name (label) for the field #}
    name: 'Overridden Label',
}) %}

{{ craft.formie.renderForm(form) }}
```

The above would override the name (label) setting for the field with a handle of `plainText`, regardless of what is defined in the field's settings.

You can also override a number of other settings, each different depending on the field.

```twig
{% do form.setFieldSettings('plainText', {
    {# Set the placeholder #}
    placeholder: 'Overridden placeholder',

    {# Change the visibility #}
    visibility: 'hidden',
}) %}

{{ craft.formie.renderForm(form) }}
```

Or, you could override the options available for a Dropdown field.

```twig
{% do form.setFieldSettings('dropdownField', {
    {# Override the options for the field #}
    options: [
        { label: 'Override One', value: 'override-one', isDefault: false },
        { label: 'Override Two', value: 'override-two', isDefault: false },
    ],
}) %}
```

:::warning
**Be warned** of the implications of this. When adding new options dynamically to fields with options, such as Dropdown, Radio Buttons and Checkboxes, you'll allow values to be changed on an inconsistent basis. Because you're changing the options for users to be able to pick from on a per-submission basis, not every submission will have the same values, as defined by the options when you create the field. This data inconsistency across submissions is something to be aware of.
:::

If fields are contained within a complex field like a Group or Repeater, you'll need to include both field handles to provide context.

```twig
{% do form.setFieldSettings('groupField.plainText', {
    name: 'Overridden Label',
}) %}
```

See the [Field Settings](docs:developers/field#field-settings) docs for a full list of available settings to override.
