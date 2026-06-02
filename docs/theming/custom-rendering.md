# Custom Rendering
Rendering your forms in a custom manner means not relying on Formie's render methods, and constructing forms how you like. You can use as much or as little of Formie's helpers as you require. Ultimately, the maintenance of these templates will be up to you, and you'll want to keep an eye on Formie's own changes to ensure you're staying up to date.

:::warning
Read [Theming Overview](/theming/overview) first if you want to compare custom rendering with the other theming options.
:::

Custom rendering is the most flexible option, but it also comes with the most responsibility. If you go down this path, you are taking over the form markup and the front-end behavior requirements that come with it.

For many projects, [Theme Config](/theming/theme-config) or [Template Overrides](/theming/template-overrides) will give you enough control with far less maintenance. Custom rendering is best reserved for cases where you really do need to take over the entire output.

## What You Need To Keep
Even when you render everything yourself, there are a few pieces you still need to keep in place for Formie to work properly:

- the browser-facing form attributes and `data-formie-*` settings on the `<form>`
- the required hidden inputs such as `handle`, `renderId`, and `requestToken`
- the current submission state, so values and validation errors can be carried across requests
- Formie's CSS and JavaScript assets, unless you are intentionally taking over that part too
- captcha output, if the form uses captchas
- `multipart/form-data` for file uploads

Features like multi-page forms, save and continue later, payments, conditions, and different submit actions all build on those basics.

## Example
The below is the most bare-bone form rendering for a Formie form. We'll use this as a base template to add more functionality.

:::tip
This guide serves as a starter. There are several aspects of templating that should be considered, such as accessibility, usability and JavaScript integration. As such, this guide should be taken as a starter for you to continue developing for your needs. It is not meant to be a copy-and-paste, complete solution.
:::

```twig
{% set form = craft.formie.forms.handle('contactUs').one() %}

{% set attributes = {
    id: form.getRenderId(),
    method: 'post',
    enctype: 'multipart/form-data',
    'accept-charset': 'utf-8',
    'data-formie': true,
    'data-formie-form': true,
    'data-formie-handle': form.handle,
    'data-formie-submit-method': form.settings.submitMethod,
    'data-formie-submit-action': form.settings.submitAction,
    'data-formie-submit-action-form-hide': form.settings.submitActionFormHide ? true : false,
    'data-formie-automatic-submission-state': form.settings.automaticSubmissionState ? true : false,
    'data-formie-error-message': form.getFrontendErrorMessage(),
    'data-formie-error-message-position': form.settings.errorMessagePosition,
    'data-formie-loading-indicator': form.settings.loadingIndicator,
    'data-formie-loading-indicator-text': form.settings.loadingIndicatorText,
    'data-formie-progress-calculation': form.settings.progressCalculation,
    'data-formie-validation-on-focus': form.settings.validationOnFocus ? true : false,
    'data-formie-validation-on-submit': form.settings.validationOnSubmit ? true : false,
    'data-formie-scroll-to-top': form.settings.scrollToTop ? true : false,
} %}

<form {{ attr(attributes) }}>
    {{ csrfInput({ autocomplete: 'off' }) }}
    {{ actionInput(form.getActionUrl()) }}
    {{ hiddenInput('submitAction', 'submit') }}
    {{ hiddenInput('handle', form.handle) }}
    {{ hiddenInput('siteId', craft.app.sites.currentSite.id) }}
    {{ hiddenInput('renderId', form.getRenderId()) }}
    {{ hiddenInput('requestToken', form.getRequestToken()) }}

    {% if form.getRedirectUrl() %}
        {{ redirectInput(form.getRedirectUrl()) }}
    {% endif %}

    {% for field in form.getFields() %}
        {% set value = field.defaultValue ?? null %}
        
        {{ field.getFrontEndInputHtml(form, value) }}
    {% endfor %}
    
    <button type="submit" data-submit-action="submit">Submit</button>
</form>
```

Stepping through the above, we prepare an array of HTML attributes, for the `<form>` element, and use Craft's `attr()` Twig function to apply them - it's a little easier than all those attributes!

:::warning
Make sure the form keeps Formie's browser attributes such as `data-formie`, `data-formie-form`, and the related `data-formie-*` settings. Without them, Formie's JavaScript will not initialize correctly, and features like client-side validation, conditions, payments, and captchas can break.
:::

If you are intentionally deferring startup for a specific form, mirror `initJs: false` with `data-formie-init="false"` on the form root so Formie's automatic startup skips that form until your own bundle mounts it.

We're then including the `actionInput`, `hiddenInput` and `csrfInput` to the form - all requirements and should not be changed. If the form has a redirect URL, we're also setting that with a `redirectInput`.

Finally, we're looping through all fields defined in the form. We're also using `getFrontEndInputHtml` to output the HTML for the field. You could write the individual `<input>` elements, but using [Template Overrides](/theming/template-overrides) for field-level changes keeps that markup modular and easier to maintain.

Next, let's add some error-handling for good UX.

```twig
{% set flashNotice = craft.formie.plugin.service.getFlash(form.getFlashNamespace(), 'notice') %}
{% set flashError = craft.formie.plugin.service.getFlash(form.getFlashNamespace(), 'error') %}

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
{% set submitted = craft.formie.plugin.service.getFlash(form.getFlashNamespace(), 'submitted') %}

<form {{ attr(attributes) }}>
    {{ csrfInput({ autocomplete: 'off' }) }}
    {{ actionInput(form.getActionUrl()) }}
    {{ hiddenInput('submitAction', 'submit') }}
    {{ hiddenInput('handle', form.handle) }}
    {{ hiddenInput('siteId', craft.app.sites.currentSite.id) }}
    {{ hiddenInput('renderId', form.getRenderId()) }}
    {{ hiddenInput('requestToken', form.getRequestToken()) }}

    {% if submission and submission.uid %}
        {{ hiddenInput('submissionUid', submission.uid) }}
    {% endif %}

    {% set errors = submission.getErrors('form') ?? null %}
    {% if errors %}
        {% for error in errors %}
            {{ error }}
        {% endfor %}
    {% endif %}

    {% for field in form.getFields() %}
        {% set value = attribute(submission, field.handle) ?? field.defaultValue ?? null %}
        {% set errors = submission.getErrors(field.handle) ?? null %}
        
        {{ field.getFrontEndInputHtml(form, value) }}

        {% if errors %}
            {% for error in errors %}
                {{ error }}
            {% endfor %}
        {% endif %}
    {% endfor %}
    
    <button type="submit" data-submit-action="submit">Submit</button>
</form>
```

We're fetching the `submission` based on the current submission for this form. For a brand-new form, this will be `null`, but if the page has reloaded with validation errors, this will be populated. For multi-page forms, this is particularly useful. We can also check the `flash` for whether the form has been submitted, to show the success message.

We also add the current submission identifier back to the form so the same in-progress submission can continue through validation and multi-page flow. We also check if there are any validation errors on the `submission` element for the form, as well as for each individual field.

Then, for each field, we're fetching the field value from the `submission` element, if it doesn't exist, we use the `defaultValue`. We're also checking for validation errors for the specific field.

### Assets
You'll usually want to include Formie's CSS and JS. If you prefer to control that separately, you can adjust the output settings in your form template or render options.

```twig
{% set form = craft.formie.forms.handle('contactUs').one() %}

{{ craft.formie.formAssets(form) }}

<form {{ attr(attributes) }}>

...
```

If you need more control, you can output CSS and JS separately:

```twig
{% set form = craft.formie.forms.handle('contactUs').one() %}

{{ craft.formie.formAssets(form, {
    includeJs: false,
}) }}

{{ craft.formie.formAssets(form, {
    includeCss: false,
}) }}

<form {{ attr(attributes) }}>

...
```

When Formie outputs its browser JavaScript for you, it also outputs the startup script configuration and the inline JSON translations seed that front-end validation and UI messages rely on. If you replace Formie's asset output entirely, your own bundle needs to take over both startup and translations.

### Captchas
If your form uses captchas, make sure you render them explicitly where you want them to appear:

```twig
{{ craft.formie.renderCaptchas(form, form.getCurrentPage()) }}
```

That should provide us with a working example to continue building. Here's the template combined:

```twig
{# Fetch the form we require #}
{% set form = craft.formie.forms.handle('contactUs').one() %}

{# Ensure the CSS/JS is rendered, according to the Form Template location #}
{{ craft.formie.formAssets(form) }}

{# Fetch the current submission - if there is one #}
{% set submission = form.getCurrentSubmission() %}
{% set submitted = craft.formie.plugin.service.getFlash(form.getFlashNamespace(), 'submitted') %}

{# Show any error or success messages for the submission #}
{% set flashNotice = craft.formie.plugin.service.getFlash(form.getFlashNamespace(), 'notice') %}
{% set flashError = craft.formie.plugin.service.getFlash(form.getFlashNamespace(), 'error') %}

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
    id: form.getRenderId(),
    method: 'post',
    enctype: 'multipart/form-data',
    'accept-charset': 'utf-8',
    'data-formie': true,
    'data-formie-form': true,
    'data-formie-handle': form.handle,
    'data-formie-submit-method': form.settings.submitMethod,
    'data-formie-submit-action': form.settings.submitAction,
    'data-formie-submit-action-form-hide': form.settings.submitActionFormHide ? true : false,
    'data-formie-automatic-submission-state': form.settings.automaticSubmissionState ? true : false,
    'data-formie-error-message': form.getFrontendErrorMessage(),
    'data-formie-error-message-position': form.settings.errorMessagePosition,
    'data-formie-loading-indicator': form.settings.loadingIndicator,
    'data-formie-loading-indicator-text': form.settings.loadingIndicatorText,
    'data-formie-progress-calculation': form.settings.progressCalculation,
    'data-formie-validation-on-focus': form.settings.validationOnFocus ? true : false,
    'data-formie-validation-on-submit': form.settings.validationOnSubmit ? true : false,
    'data-formie-scroll-to-top': form.settings.scrollToTop ? true : false,
} %}

<form {{ attr(attributes) }}>
    {{ csrfInput({ autocomplete: 'off' }) }}
    {{ actionInput(form.getActionUrl()) }}
    {{ hiddenInput('submitAction', 'submit') }}
    {{ hiddenInput('handle', form.handle) }}
    {{ hiddenInput('siteId', craft.app.sites.currentSite.id) }}
    {{ hiddenInput('renderId', form.getRenderId()) }}
    {{ hiddenInput('requestToken', form.getRequestToken()) }}

    {# Ensure we update the same submission on subsequent saves (if validation fails) #}
    {% if submission and submission.uid %}
        {{ hiddenInput('submissionUid', submission.uid) }}
    {% endif %}

    {# Show any validation errors for the form #}
    {% set errors = submission.getErrors('form') ?? null %}
    {% if errors %}
        {% for error in errors %}
            {{ error }}
        {% endfor %}
    {% endif %}

    {# Render each field, according to its field template #}
    {% for field in form.getFields() %}
        {# Fetch the value if one exists, or use the default #}
        {% set value = attribute(submission, field.handle) ?? field.defaultValue ?? null %}
        {% set errors = submission.getErrors(field.handle) ?? null %}
        
        {{ field.getFrontEndInputHtml(form, value) }}

        {# Show any field-specific errors #}
        {% if errors %}
            {% for error in errors %}
                {{ error }}
            {% endfor %}
        {% endif %}
    {% endfor %}

    {{ craft.formie.renderCaptchas(form, form.getCurrentPage()) }}
    
    <button type="submit" data-submit-action="submit">Submit</button>
</form>
```

### File Uploads
If your form contains File Upload fields, you'll need to set the `<form>` element to use `multipart/form-data`.

```twig
{% set attributes = {
    method: 'post',
    enctype: 'multipart/form-data',
    'data-formie': true,
    'data-formie-form': true,
    'data-formie-init': 'false',
} %}

<form {{ attr(attributes) }}>

```

Without this, file uploads will not work.

## Common Things People Miss

- Leaving out Formie's browser attributes on the `<form>`, which stops the front-end behavior from initializing correctly.
- Using outdated hidden inputs, or forgetting to pass the current submission back through after validation fails.
- Forgetting to render captchas at all.
- Not using `multipart/form-data` when the form includes file uploads.
- Assuming a one-page form is the whole story. Multi-page forms, payments, save and continue later, and conditional behavior all add more moving parts.

### What's Not Covered
Whilst we've covered the basics, there's still plenty left to address, such as different submit actions, multi-page navigation, save-and-resume flows, and payment-specific behavior. For more complete examples, consult the templates on [Formie's GitHub](https://github.com/verbb/formie/tree/craft-5/src/templates/_special/form-template).

:::tip
Check out the raw templates on [Formie's GitHub](https://github.com/verbb/formie/tree/craft-5/src/templates/_special/form-template) for the most up to date reference.
:::
