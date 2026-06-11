# Overriding Settings

Override settings in a template when the saved form should stay the same, but one render needs slightly different behavior.

This works by loading the form, changing the setting on the form object, then rendering that form object. It can be useful for template-specific redirects, field labels, placeholders, visibility, or dynamic option lists.

::: warning
These overrides rely on the Twig template being evaluated before the form is rendered. On a statically cached page, the cached HTML will keep the settings from the render that created the cache. Formie's cached-form support can refresh request tokens for the cached form, but it does not re-run template overrides or rebuild the field markup on each cached page load. See [Cached Forms](/frontend/cached-forms) for how static-cache support works.
:::

## Form Settings

Use `setSettings()` when the setting belongs to the form itself.

```twig
{% set form = craft.formie.forms.handle('contactForm').one() %}

{% do form.setSettings({
    redirectUrl: '/thanks',
}) %}

{{ craft.formie.renderForm(form) }}
```

Keep these overrides deliberate. If the setting should always apply to the form, it is usually better to save it in the form builder.

## Field Settings

Use `setFieldSettings()` when the change belongs to a field.

```twig
{% set form = craft.formie.forms.handle('contactForm').one() %}

{% do form.setFieldSettings('firstName', {
    label: 'Preferred name',
    placeholder: 'Enter your preferred name',
}) %}

{{ craft.formie.renderForm(form) }}
```

### Required state

Use a real boolean when changing whether a field is required. The string `'false'` is still a non-empty value in PHP/Twig contexts, so it can behave like an enabled setting.

```twig
{% set form = craft.formie.forms.handle('portfolioForm').one() %}

{% do form.setFieldSettings('workFeaturedAudio', {
    required: false,
}) %}

{{ craft.formie.renderForm(form) }}
```

This is useful for edit forms where a field is required for new submissions, but optional when updating existing content.

File Upload fields are a special case because browsers do not allow file inputs to be prefilled. When an edit form does not include a new file value, Formie leaves the existing uploaded file alone on the submission instead of clearing it.

For option fields such as Dropdown, Radio and Checkboxes, you can override the available options before rendering.

```twig
{% do form.setFieldSettings('department', {
    options: [
        { label: 'Support', value: 'support', default: false },
        { label: 'Billing', value: 'billing', default: false },
        { label: 'Sales', value: 'sales', default: false },
    ],
}) %}

{{ craft.formie.renderForm(form) }}
```

::: warning
Changing options dynamically means different submissions may have been made against different option sets. That can be fine for context-specific forms, but it is worth planning for in exports, reporting and integrations.
:::

For nested fields inside Group or Repeater fields, include the parent field handle so Formie knows which nested field you mean.

```twig
{% do form.setFieldSettings('contactDetails.firstName', {
    label: 'Preferred name',
}) %}
```

### Container and input attributes

`containerAttributes` and `inputAttributes` accept the same Craft-style attribute map you would pass to Craft’s `attr()` helper:

```twig
{% do form.setFieldSettings('myCustomField', {
    inputAttributes: {
        readonly: true,
        data: {
            foo: 'bar',
        },
    },
}) %}
```

That replaces any attributes already saved on the field.

To add attributes without removing the saved ones, use `mergeInputAttributes` or `mergeContainerAttributes`:

```twig
{% do form.setFieldSettings('myCustomField', {
    mergeInputAttributes: {
        readonly: true,
    },
}) %}
```

The editable-table format used in the form builder is still supported when you need it:

```twig
{% do form.setFieldSettings('myCustomField', {
    inputAttributes: [
        { label: 'readonly', value: true },
    ],
}) %}
```

Passing an invalid attribute format throws an exception instead of being silently ignored.
