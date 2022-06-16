# Custom Rendering
We highly recommend rendering your forms using one of the following methods:

- Using the `render` function, [`renderForm`](docs:template-guides/rendering-forms), [`renderPage`](docs:template-guides/rendering-pages) or [`renderField`](docs:template-guides/rendering-fields).
- Overriding [Form Templates](docs:template-guides/form-templates).
- Modifying form, pages or fields using [Template Hooks](docs:developers/hooks).

Either of these methods make use of core Formie rendering functionality, giving you the benefit of any updates that we make to the internals of Formie. This in turn, makes maintaining your forms a breeze.

However, you can take 100% control over rendering your forms if your choose to, but there are certainly some caveat's and things to be aware of, which we'll cover below.

## Example
The below is the most bare-bone form rendering for a Formie form. We'll use this as a base template to add more functionality.

:::tip
This guide serves as a starter. There are several aspects of templating that should be considered, such as accessibility, usability and JavaScript integration. As such, this guide should be taken as a starter for you to continue developing for your needs. It is not meant to be a copy-and-paste, total solution.
:::

```twig
{% set form = craft.formie.forms.handle('contactUs').one() %}

{% set attributes = {
    id: form.formId,
    method: 'post',
    data: {
        config: form.configJson,
    },
} %}

<form {{ attr(attributes) }}>
    {{ actionInput('formie/submissions/submit') }}
    {{ hiddenInput('handle', form.handle) }}
    {{ csrfInput() }}

    {% if form.getRedirectUrl() %}
        {{ redirectInput(form.getRedirectUrl()) }}
    {% endif %}

    {% for field in form.getCustomFields() %}
        {% namespace field.namespace %}
            {% set value = field.defaultValue ?? null %}
            {% set id = (form.handle | kebab) ~ '-' ~ (field.handle | kebab) %}
            {% set labelId = "#{id}-label" %}
            {% set dataId = field.getHtmlDataId(form) %}

            {% set inputOptions = {
                id: id,
                labelId: labelId,
                dataId: dataId,
            } %}
            
            {{ field.getFrontEndInputHtml(form, value, inputOptions) }}
        {% endnamespace %}
    {% endfor %}
    
    <button type="submit">Submit</button>
</form>
```

Stepping through the above, we prepare an array of HTML attributes, for the `<form>` element, and use Craft's `attr()` Twig function to apply them - it's a little easier than all those attributes!

Make sure to use `{{ form.formId }}` for the `id` attribute, and `{{ form.configJson }}` for the `data-config` attribute. These are the only two things Formie needs to hook up the JavaScript used to handle forms. Everything else related to markup is up to you.

We're then including the `actionInput`, `hiddenInput` and `csrfInput` to the form - all requirements and should not be changed. If the form has a redirect URL, we're also setting that with a `redirectInput`.

Finally, we're looping through all fields defined in the form, and namespacing them, so Formie can grab the field values. We're also using `getFrontEndInputHtml` to output the HTML for the field. You could write the individual `<input>` elements, but we'd highly recommend you use the [Form Templates](docs:template-guides/form-templates) to override individual field HTML. The reason is simple - you're keeping field HTML modular, so it's easily reusable across multiple forms.

Be sure to include the `id` and `labelId` for accessibility, but some fieldtypes rely on this to hook in JavaScript behaviour.

Next, let's add some error-handling for good UX.

```twig
{% set flashNotice = craft.formie.plugin.service.getFlash(form.id, 'notice') %}
{% set flashError = craft.formie.plugin.service.getFlash(form.id, 'error') %}

{% if flashNotice %}
    <div role="alert">
        {{ flashNotice | raw }}
    </div>
{% endif %}

{% if flashError %}
    <div role="alert">
        {{ flashError | raw }}
    </div>
{% endif %}

<form {{ attr(attributes) }}>

...
```

Here, we've added flash messages for form-wide errors and success. We'll add some more shortly.

Then, we want to add information about the submission. This is important if the form is submitted, but validation fails. You'll want to retain the submission information on the form, rather than getting the user to fill in their details from scratch. It's also important for multi-page forms.

```twig
{% set submission = form.getCurrentSubmission() %}
{% set submitted = craft.formie.plugin.service.getFlash(form.id, 'submitted') %}

<form {{ attr(attributes) }}>
    {{ actionInput('formie/submissions/submit') }}
    {{ hiddenInput('handle', form.handle) }}
    {{ csrfInput() }}

    {% if submission and submission.id %}
        {{ hiddenInput('submissionId', submission.id) }}
    {% endif %}

    {% set errors = submission.getErrors('form') ?? null %}
    {% if errors %}
        {% for error in errors %}
            {{ error }}
        {% endfor %}
    {% endif %}

    {% for field in form.getCustomFields() %}
        {% namespace field.namespace %}
            {% set id = (form.handle | kebab) ~ '-' ~ (field.handle | kebab) %}
            {% set labelId = "#{id}-label" %}
            {% set dataId = field.getHtmlDataId(form) %}

            {% set inputOptions = {
                id: id,
                labelId: labelId,
                dataId: dataId,
            } %}

            {% set value = attribute(submission, field.handle) ?? field.defaultValue ?? null %}
            {% set errors = submission.getErrors(field.handle) ?? null %}
            
            {{ field.getFrontEndInputHtml(form, value, inputOptions) }}

            {% if errors %}
                {% for error in errors %}
                    {{ error }}
                {% endfor %}
            {% endif %}
        {% endnamespace %}
    {% endfor %}
    
    <button type="submit">Submit</button>
</form>
```

We're fetching the `submission` based on the current submission for this form. For a brand-new form, this will be `null`, but if the page has reloaded with validation errors, this will be populated. For multi-page forms, this is particularly useful. We can also check the `flash` for whether the form has been submitted, to show the success message.

We also add the `submissionId` as a `hiddenInput` if we're trying to submit the form again. We also check if there are any validation errors on the `submission` element for the form, as well as for each individual field.

Then, for each field, we're fetching the field value from the `submission` element, if it doesn't exist, we use the `defaultValue`. We're also checking for validation errors for the specific field.

Last, but not least - we'll want to include Formie's CSS and JS. If you'd rather include your own, either exclude this, or turn these off in your [Form Templates](docs:template-guides/form-templates).

```twig
{% set form = craft.formie.forms.handle('contactUs').one() %}

{% do craft.formie.registerAssets(form.handle) %}

<form {{ attr(attributes) }}>

...
```

It's important to include this `registerAssets` before the `<form>` rendering tag. You could also include them separately as below:

```twig
{% set form = craft.formie.forms.handle('contactUs').one() %}

{% do craft.formie.renderFormCss(form.handle) %}
{% do craft.formie.renderFormJs(form.handle) %}

<form {{ attr(attributes) }}>

...
```

### Captchas
If your form uses captchas, it's important that you include a template hook to tell the form where to inject required HTML generated by the captcha. Be sure your form includes the following hook to ensure captchas work:

```
{% hook 'formie.buttons.before' %}
```

That should provide us with a working example to continue building. Here's the template combined:

```twig
{# Fetch the form we require #}
{% set form = craft.formie.forms.handle('contactUs').one() %}

{# Ensure the CSS/JS is rendered, according to the Form Template location #}
{% do craft.formie.registerAssets(form.handle) %}

{# Fetch the current submission - if there is one #}
{% set submission = form.getCurrentSubmission() %}
{% set submitted = craft.formie.plugin.service.getFlash(form.id, 'submitted') %}

{# Show any error or success messages for the submission #}
{% set flashNotice = craft.formie.plugin.service.getFlash(form.id, 'notice') %}
{% set flashError = craft.formie.plugin.service.getFlash(form.id, 'error') %}

{% if flashNotice %}
    <div role="alert">
        {{ flashNotice | raw }}
    </div>
{% endif %}

{% if flashError %}
    <div role="alert">
        {{ flashError | raw }}
    </div>
{% endif %}

{# Generate required attributes for the `<form>` element #}
{% set attributes = {
    id: form.formId,
    method: 'post',
    data: {
        config: form.configJson,
    },
} %}

<form {{ attr(attributes) }}>
    {{ actionInput('formie/submissions/submit') }}
    {{ hiddenInput('handle', form.handle) }}
    {{ csrfInput() }}

    {# Ensure we update the same submission on subsequent saves (if validation fails) #}
    {% if submission and submission.id %}
        {{ hiddenInput('submissionId', submission.id) }}
    {% endif %}

    {# Show any validation errors for the form #}
    {% set errors = submission.getErrors('form') ?? null %}
    {% if errors %}
        {% for error in errors %}
            {{ error }}
        {% endfor %}
    {% endif %}

    {# Render each field, according to its field template #}
    {% for field in form.getCustomFields() %}
        {% namespace field.namespace %}
            {% set id = (form.handle | kebab) ~ '-' ~ (field.handle | kebab) %}
            {% set labelId = "#{id}-label" %}
            {% set dataId = field.getHtmlDataId(form) %}

            {# Set required attributes for the field #}
            {% set inputOptions = {
                id: id,
                labelId: labelId,
                dataId: dataId,
            } %}

            {# Fetch the value if one exists, or use the default #}
            {% set value = attribute(submission, field.handle) ?? field.defaultValue ?? null %}
            {% set errors = submission.getErrors(field.handle) ?? null %}
            
            {{ field.getFrontEndInputHtml(form, value, inputOptions) }}

            {# Show any field-specific errors #}
            {% if errors %}
                {% for error in errors %}
                    {{ error }}
                {% endfor %}
            {% endif %}
        {% endnamespace %}
    {% endfor %}

    {% hook 'formie.buttons.before' %}
    
    <button type="submit">Submit</button>
</form>
```

### What's Not Covered
Whilst we've covered the basics, there's still plenty of things left to address, such as handling the different forms of submission (redirecting the user away, hiding the form, only showing a message), and multi-page forms. That's beyond the scope of this guide, and we'd encourage you to consult the templates on [Formie's GitHub](https://github.com/verbb/formie/tree/craft-3/src/templates/_special).

### Next Steps
The above is a quick guide to the basics, but be warned that you'll be required to keep an eye on Formie's templates and development, in order to keep up with any core changes for your templates. We highly recommend you test your template code to ensure Formie's JavaScript works with your HTML markup as well, particularly for things like Captchas.

:::tip
Check out the raw templates on [Formie's GitHub](https://github.com/verbb/formie/tree/craft-3/src/templates/_special/form-template) - they'll be the most up to date. This example serves as a brief, cut-down version of what Formie does under the hood, to use these templates as further inspiration for your own templates.
:::
