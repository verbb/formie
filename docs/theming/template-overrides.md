# Template Overrides
While Formie's default templates suit most needs, you can of course roll your own templates, so you have total control over the form, field, layout and more.

:::warning
Read [Theming Overview](/theming/overview) first if you want to compare template overrides with the other theming options.
:::

The great thing about Formie's custom templates is that it doesn't have to be all-or-nothing. You can choose to override a single template, or all. For instance, you might have very specific markup needs to a Dropdown field. You can override just the template for the dropdown field, and nothing else.

## Form Templates
To get started, navigate to **Formie** → **Settings** → **Form Templates** and create a new template. Enable the **Use Custom Template** setting to be able to define a template directory for your custom templates to sit. For example, if your templates exist in `templates/_forms`, you would enter `_forms`.

:::tip
Be sure to select a _directory_ when setting the template path, as there are multiple templates Formie can override. You can look at the [directory structure](https://github.com/verbb/formie/tree/craft-5/src/templates/_special/form-template) for more.
:::

You can also use the **Copy Templates** setting when creating, which will copy all of Formie's default templates into the directory you've specified. That way, you're starting off with the templates Formie already uses as a basis for your custom templates.

:::tip
You can't modify Formie's default Form Templates. Instead, you must create a new Form Template, and ensure your forms use that. This gives you the benefit of being able to easily manage _multiple_ custom templates across your forms.
:::

Before we dive in, it's worth taking the time to understand the structure of how templates go together.

:::tip
We're using the `.html` extension here for clarity. You can use `.twig` or whatever you have set in your [defaultTemplateExtensions](https://craftcms.com/docs/4.x/config/config-settings.html#defaulttemplateextensions) for the actual files.
:::

- `form.html`
- `field.html`
- `page.html`
- `form/`
    - `body.html`
    - `footer.html`
    - `header.html`
    - `hidden-inputs.html`
    - `navigation.html`
    - `progress.html`
    - `tabs.html`
    - `...`
- `page/`
    - `body.html`
    - `buttons.html`
    - `captchas.html`
    - `footer.html`
    - `header.html`
- `field/`
    - `errors.html`
    - `instructions.html`
    - `label.html`
- `fields/`
    - `address.html`
    - `agree.html`
    - `categories.html`
    - `date/`
        - `_calendar.html`
        - `_datePicker.html`
        - `...`
    - `group/`
        - `_row.html`
        - `index.html`
    - `name/`
        - `_multiple.html`
        - `_single.html`
        - `index.html`
    - `repeater/`
        - `_row.html`
        - `index.html`
    - `table/`
        - `_row.html`
        - `index.html`
    - `...`

Let's start with the top-level templates.

:::tip
Check out the raw templates on [Formie's GitHub](https://github.com/verbb/formie/tree/craft-5/src/templates/_special) - they'll be the most up to date.
:::

## Overriding Form Templates
To override the form template, provide a file named `form.html` in your template directory.

### Available Template Variables
These templates have access to the following variables:

Variable | Description
--- | ---
`form` | A [Form](/reference/form) object, for the form instance this template is for.
`submission` | The current [Submission](/reference/submission) object this form may or may not have.
`renderOptions` | A collection of [Render Options](/templates/render-options).

## Overriding Page Templates
To override the page template, provide a file named `page.html` in your template directory.

### Available Template Variables
These templates have access to the following variables:

Variable | Description
--- | ---
`form` | A [Form](/reference/form) object that this field belongs to.
`page` | A [Page](/reference/page) object, for the page instance this template is for.
`renderOptions` | A collection of [Render Options](/templates/render-options).

## Overriding Field Wrapper Templates
To override the field template, provide a file named `field.html` in your template directory. This is the wrapper template around all fields. You can also override individual field types' templates, rather than changing the template for every field, regardless of type.

### Available Template Variables
These templates have access to the following variables:

Variable | Description
--- | ---
`form` | A [Form](/reference/form) object that this field belongs to.
`field` | A [Field](/reference/field) object, for the field instance this template is for.
`handle` | The handle of the field.
`element` | The current [Submission](/reference/submission) object this form may or may not have.
`renderOptions` | A collection of [Render Options](/templates/render-options).

## Overriding Field Templates
You'll notice the above structure includes the `fields/` directory. Inside this directory are a mixture of folders and individual files, each representing a template that you're able to override.

First, you'll need to identify the template's name. It's derived from the PHP class name for the field, converted to a "kebab" string. For easy reference, you can use the below table.

Class Name | Template
--- | ---
`Address` | `address.html`
`Agree` | `agree.html`
`Calculations` | `calculations.html`
`Categories` | `categories.html`
`Checkboxes` | `checkboxes.html`
`Date` | `date.html`
`Dropdown` | `dropdown.html`
`Email` | `email.html`
`Entries` | `entries.html`
`FileUpload` | `file-upload.html`
`Forms` | `forms.html`
`Group` | `group/index.html`
`Heading` | `heading.html`
`Hidden` | `hidden.html`
`Html` | `html.html`
`MultiLineText` | `multi-line-text.html`
`Name` | `name/index.html`
`Number` | `number.html`
`Password` | `password.html`
`Payment` | `payment.html`
`Phone` | `phone.html`
`Products` | `products.html`
`Radio` | `radio.html`
`Recipients` | `recipients.html`
`Repeater` | `repeater/index.html`
`Section` | `section.html`
`Signature` | `signature.html`
`SingleLineText` | `single-line-text.html`
`Submissions` | `submissions.html`
`Summary` | `summary.html`
`Table` | `table/index.html`
`Tags` | `tags.html`
`Users` | `users.html`
`Variants` | `variants.html`

Adding a template file in your specified template directory will use that template file over the ones Formie provide.

You might also have noticed we've shown `date` in a folder. Due to how Twig resolves templates, the below are equivalent:

```
fields/date.html - Is the same as - fields/date/index.html
```

For complex fields that have multiple templates, we've used folders to organise multiple templates in a single folder. You're welcome to follow this same pattern, but you're not forced to.

For example, the Date field, has the following templates in a folder:

- `fields/date/_calendar.html`
- `fields/date/_datepicker.html`
- `fields/date/_dropdowns.html`
- `fields/date/_inputs.html`
- `fields/date/index.html`

This is because the date field has many display configurations. If you want to override the templates for this field, you just need to alter the `index.html` file. You can use the includes (denoted by `_`), or you don't have to.

## Overriding Partials
You'll have noticed in the template structure above that Formie uses partials throughout the form, page, and field templates. This helps keep the markup modular, and it also means you can override just one part of the output without replacing an entire top-level template.

For example, `form.html` includes partials like `form/navigation.html`, `form/progress.html`, and `form/tabs.html`. Rather than overriding `form.html` just to change one of those, you can override the partial directly.

For example, if you want to change the page navigation for a multi-page form, you can create `form/navigation.html` in your custom template directory and leave the rest of the form templates alone.

### How it Works
Formie's templates use a custom Twig function for partials:

```twig
{{ formieInclude('form/navigation') }}
```

This is different to a normal Twig include such as:

```twig
{% include 'form/navigation' %}
```

The difference is how the partial path is resolved. A regular `{% include %}` is relative to the current template. `formieInclude()` checks your override directory first, then falls back to Formie's default templates.

### Example Partial Override
For example, if you only want to change how field errors are rendered, create `field/errors.html` in your template directory:

```twig
{% if field.getErrors(element) %}
    <ul class="my-field-errors">
        {% for error in field.getErrors(element) %}
            <li>{{ error }}</li>
        {% endfor %}
    </ul>
{% endif %}
```

That will override just the error partial, while leaving the rest of the field template structure in place.
