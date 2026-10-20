# Custom Rendering
Rendering your forms in a custom manner means not relying on Formie's render methods, and constructing forms how you like. You can use as much or as little of Formie's helpers as you require. Ultimately, the maintenance of these templates will be up to you, and you'll want to keep an eye on Formie's own changes to ensure you're staying up to date.

:::warning
Read [Theming Overview](/theming/overview) first if you want to compare custom rendering with the other theming options.
:::

Custom rendering is the most flexible option, but it also comes with the most responsibility. If you go down this path, you are taking over the form markup and the front-end behaviour requirements that come with it.

For many projects, [Theme Config](/theming/theme-config) or [Template Overrides](/theming/template-overrides) will give you enough control with far less maintenance. Custom rendering is best reserved for cases where you really do need to take over the entire output.

## What You Need to Keep
Even when you render everything yourself, there are a few pieces you still need to keep in place for Formie to work properly:

- the browser-facing form attributes and `data-formie-*` settings on the `<form>`
- the required hidden inputs such as `handle`, `renderId`, and `requestToken`
- the current submission state, so values and validation errors can be carried across requests
- Formie's CSS and JavaScript assets, unless you are intentionally taking over that part too
- captcha output, if the form uses captchas
- `multipart/form-data` for file uploads

Features like multi-page forms, save and continue later, payments, conditions, and different completion behaviors all build on those basics.

## Example
The following example renders a single-page contact form. Replace `contactUs` with your form's handle. We'll use this as a base template to add submission feedback. Multi-page forms, file uploads and payments need additional markup and request data beyond this basic example. This example uses an optional helper for the individual fields; [Writing Field HTML Yourself](#writing-field-html-yourself) shows how to replace that too.

The minimal example below assumes honeypot and minimum-submit-time protection are disabled. These protections are enabled by default; if you keep them enabled, add the conditional inputs in [Spam Protection](#spam-protection), or use the combined example further down.

:::tip
This guide serves as a starter. There are several aspects of templating that should be considered, such as accessibility, usability and JavaScript integration. As such, this guide should be taken as a starter for you to continue developing for your needs. It is not meant to be a copy-and-paste, complete solution.
:::

```twig
{% set form = craft.formie.forms.handle('contactUs').one() %}

{{ craft.formie.formAssets(form) }}

{% set attributes = {
    id: form.getRenderId(),
    method: 'post',
    enctype: 'multipart/form-data',
    'accept-charset': 'utf-8',
    'data-formie': true,
    'data-formie-form': true,
    'data-formie-handle': form.handle,
    'data-formie-csrf-param': craft.app.request.csrfParam,
    'data-formie-modules': craft.formie.plugin.browserModuleManifestBuilder.buildForSurface(form, 'server-rendered').toArray() | json_encode,
    'data-formie-submit-method': form.settings.submitMethod,
    'data-formie-completion-behavior': form.settings.completionBehavior,
    'data-formie-hide-form-after-submit': form.settings.hideFormAfterSubmit ? 'true' : 'false',
    'data-formie-automatic-submission-state': form.settings.automaticSubmissionState ? 'true' : 'false',
    'data-formie-error-message': form.getFrontendErrorMessage(),
    'data-formie-error-message-position': form.settings.errorMessagePosition,
    'data-formie-loading-indicator': form.settings.loadingIndicator,
    'data-formie-loading-indicator-text': form.settings.loadingIndicatorText,
    'data-formie-progress-calculation': form.settings.progressCalculation,
    'data-formie-validation-on-focus': form.settings.validationOnFocus ? 'true' : 'false',
    'data-formie-validation-on-submit': form.settings.validationOnSubmit ? 'true' : 'false',
    'data-formie-scroll-to-top': form.settings.scrollToTop ? 'true' : 'false',
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
        {{ craft.formie.renderField(form, field) }}
    {% endfor %}
    
    <button type="submit" data-submit-action="submit">Submit</button>
</form>
```

The `attributes` object contains ordinary HTML attributes. Craft's `attr()` function escapes and outputs them on your own `<form>` element. You can add your own classes and change the surrounding markup without using Formie's theme configuration or custom Twig tags. The `data-formie-*` attributes connect that markup to Formie's browser behaviour; keep them when using its JavaScript. `data-formie-modules` supplies the browser modules needed by your fields and captchas without controlling their HTML or theme. Boolean settings use the strings `'true'` and `'false'`, because `attr()` omits attributes whose value is the boolean `false`.

:::warning
Make sure the form keeps Formie's browser attributes such as `data-formie`, `data-formie-form`, and the related `data-formie-*` settings. Without them, Formie's JavaScript will not initialise correctly, and features like client-side validation, conditions, payments, and captchas can break.
:::

If you are intentionally deferring startup for a specific form, mirror `initJs: false` with `data-formie-init="false"` on the form root so Formie's automatic startup skips that form until your own bundle mounts it.

The hidden inputs route the submission to Formie, identify the form and site, and provide its render ID and signed request token. If the form has a redirect URL, the example also adds a `redirectInput`. Inputs for enabled spam protections are covered separately under [Spam Protection](#spam-protection).

Finally, `craft.formie.renderField(form, field)` renders each field with its label, input, validation rules and error container. It also restores submitted values or applies defaults. Rendering only an input omits the field wrapper that Formie's browser validation needs. You can use [Template Overrides](/theming/template-overrides) with this helper, or replace it with your own field HTML as shown below.

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

Then, we want to add information about the submission. This is important if the form is submitted, but validation fails. You'll want to retain the submission information on the form, rather than getting the user to fill in their details from scratch. This partial example replaces the form block above:

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
        {{ hiddenInput('expectedVersion', submission.stateVersion) }}
    {% endif %}

    {% set errors = submission ? submission.submissionErrors.forValuePath('form') : [] %}
    {% if errors %}
        {% for error in errors %}
            {{ error }}
        {% endfor %}
    {% endif %}

    {% for field in form.getFields() %}
        {{ craft.formie.renderField(form, field) }}
    {% endfor %}
    
    <button type="submit" data-submit-action="submit">Submit</button>
</form>
```

We're fetching the `submission` based on the current submission for this form. For a brand-new form, this will be `null`, but if the page has reloaded with validation errors, this will be populated. We can also check the `flash` for whether the form has been submitted, to show the success message.

For an existing saved submission, `submissionUid` identifies the record and `expectedVersion` prevents an older request from overwriting newer changes. A new single-page submission does not need either input. We also check for form-wide validation errors on the `submission` element.

The field renderer restores each field's value and displays its validation errors, including errors for nested fields.

### Writing Field HTML Yourself
`craft.formie.renderField()` is optional. It uses Formie's field templates and theme configuration. To own the field markup completely, replace the field loop with your own HTML. This example renders a Single-Line Text field whose handle is `message`:

```twig
{% set field = form.getFieldByHandle('message') %}
{% set submission = form.getCurrentSubmission() %}
{% set value = field.getElementValue(submission) %}
{% set errors = submission ? submission.submissionErrors.forValuePath(field.errorKey()) : [] %}
{% set inputId = form.getRenderId() ~ '-message' %}

<div {{ attr({
    'data-formie-field': true,
    'data-formie-field-handle': field.handle,
    'data-formie-validation': field.getBrowserValidationRulesJson(),
}) }}>
    <label for="{{ inputId }}" data-formie-field-label>{{ field.label }}</label>
    <input {{ attr({
        type: 'text',
        id: inputId,
        name: 'fields[message]',
        value: value,
        required: field.required,
        'data-formie-input': true,
    }) }}>
    <div data-formie-field-errors>
        {% for error in errors %}
            <p>{{ error }}</p>
        {% endfor %}
    </div>
</div>
```

The `fields[message]` name connects the input to the field configured in Formie. The wrapper's `data-formie-validation` contains its current rules; the label and error hooks let Formie's JavaScript display feedback. If you supply your own JavaScript validation instead, these browser hooks are optional, but the input name and server-side validation still apply. Other field types need their own markup and value shape, especially nested and multi-value fields.

### Assets
The example includes Formie's CSS and JS. If you prefer to control that separately, you can adjust the output settings in your form template or render options. These partial snippets show the asset calls before your form block:

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

### Spam Protection
The honeypot and timer support optional spam-protection settings. They are not field-validation inputs. If you do not want those protections, disable the corresponding settings under **Spam Protection**; omitting the inputs while leaving the checks enabled can cause submissions to be rejected as spam.

When enabled, add this markup inside your `<form>`. It is written explicitly so you can customise it without including a Formie template:

```twig
{% set guardSettings = craft.formie.plugin.settings %}

{% if guardSettings.enableHoneypot %}
    <div aria-hidden="true" style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden;">
        <label for="{{ form.getRenderId() }}-honeypot">Leave this field blank</label>
        <input type="text" id="{{ form.getRenderId() }}-honeypot"
            name="{{ guardSettings.honeypotFieldName }}" value=""
            tabindex="-1" autocomplete="off">
    </div>
{% endif %}

{% if guardSettings.enableMinimumSubmitTime or guardSettings.enableFormSubmitExpiration %}
    {{ hiddenInput('formStartedAt', '') }}
{% endif %}
```

Formie's JavaScript fills `formStartedAt` with the form's start time in milliseconds. The server uses it for minimum-submit-time and form-expiration checks; it is not an analytics field. If you replace Formie's JavaScript, set the input to `Date.now()` when your form initialises, rather than when it submits.

### Captchas
If your form uses captchas, make sure you render them explicitly where you want them to appear:

```twig
{{ craft.formie.renderCaptchas(form, form.getCurrentPage()) }}
```

Here is the template combined, with the spam-protection inputs included only when their settings are enabled:

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

{% set attributes = {
    id: form.getRenderId(),
    method: 'post',
    enctype: 'multipart/form-data',
    'accept-charset': 'utf-8',
    'data-formie': true,
    'data-formie-form': true,
    'data-formie-handle': form.handle,
    'data-formie-csrf-param': craft.app.request.csrfParam,
    'data-formie-modules': craft.formie.plugin.browserModuleManifestBuilder.buildForSurface(form, 'server-rendered').toArray() | json_encode,
    'data-formie-submit-method': form.settings.submitMethod,
    'data-formie-completion-behavior': form.settings.completionBehavior,
    'data-formie-hide-form-after-submit': form.settings.hideFormAfterSubmit ? 'true' : 'false',
    'data-formie-automatic-submission-state': form.settings.automaticSubmissionState ? 'true' : 'false',
    'data-formie-error-message': form.getFrontendErrorMessage(),
    'data-formie-error-message-position': form.settings.errorMessagePosition,
    'data-formie-loading-indicator': form.settings.loadingIndicator,
    'data-formie-loading-indicator-text': form.settings.loadingIndicatorText,
    'data-formie-progress-calculation': form.settings.progressCalculation,
    'data-formie-validation-on-focus': form.settings.validationOnFocus ? 'true' : 'false',
    'data-formie-validation-on-submit': form.settings.validationOnSubmit ? 'true' : 'false',
    'data-formie-scroll-to-top': form.settings.scrollToTop ? 'true' : 'false',
} %}

<form {{ attr(attributes) }}>
    {{ csrfInput({ autocomplete: 'off' }) }}
    {{ actionInput(form.getActionUrl()) }}
    {{ hiddenInput('submitAction', 'submit') }}
    {{ hiddenInput('handle', form.handle) }}
    {{ hiddenInput('siteId', craft.app.sites.currentSite.id) }}
    {{ hiddenInput('renderId', form.getRenderId()) }}
    {{ hiddenInput('requestToken', form.getRequestToken()) }}

    {% set guardSettings = craft.formie.plugin.settings %}

    {% if guardSettings.enableHoneypot %}
        <div aria-hidden="true" style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden;">
            <label for="{{ form.getRenderId() }}-honeypot">Leave this field blank</label>
            <input type="text" id="{{ form.getRenderId() }}-honeypot"
                name="{{ guardSettings.honeypotFieldName }}" value=""
                tabindex="-1" autocomplete="off">
        </div>
    {% endif %}

    {% if guardSettings.enableMinimumSubmitTime or guardSettings.enableFormSubmitExpiration %}
        {{ hiddenInput('formStartedAt', '') }}
    {% endif %}

    {# Ensure we update the same submission on subsequent saves (if validation fails) #}
    {% if submission and submission.uid %}
        {{ hiddenInput('submissionUid', submission.uid) }}
        {{ hiddenInput('expectedVersion', submission.stateVersion) }}
    {% endif %}

    {# Show any validation errors for the form #}
    {% set errors = submission ? submission.submissionErrors.forValuePath('form') : [] %}
    {% if errors %}
        {% for error in errors %}
            {{ error }}
        {% endfor %}
    {% endif %}

    {# Render each field, according to its field template #}
    {% for field in form.getFields() %}
        {{ craft.formie.renderField(form, field) }}
    {% endfor %}

    {{ craft.formie.renderCaptchas(form, form.getCurrentPage()) }}
    
    <button type="submit" data-submit-action="submit">Submit</button>
</form>
```

### Multi-Page Forms
The single-page example does not need a `pageId` input. Multi-page forms need the current page ID, page containers and navigation controls, as well as submission continuity between requests. Include `{{ hiddenInput('pageId', form.getCurrentPage().id) }}` when building that flow, and use the [default page templates](https://github.com/verbb/formie/tree/craft-5/src/templates/_special/form-template) as a reference for the additional browser hooks.

### File Uploads
The form must use `multipart/form-data` for uploads, as shown in the attribute object. File Upload fields also need Formie's upload tokens and payload metadata, so adding the encoding alone is not a complete upload implementation. Consult the [default hidden-input template](https://github.com/verbb/formie/blob/craft-5/src/templates/_special/form-template/form/hidden-inputs.html) and upload handling when constructing your own flow. [Template Overrides](/theming/template-overrides) are an alternative if you want Formie to supply that request context.

## Common Things People Miss

- Leaving out Formie's browser attributes on the `<form>`, which stops the front-end behaviour from initializing correctly.
- Using outdated hidden inputs, or forgetting to pass the current submission back through after validation fails.
- Omitting inputs or captcha output required by enabled spam protections.
- Not using `multipart/form-data` when the form includes file uploads.
- Assuming a one-page form is the whole story. Multi-page forms, payments, save and continue later, and conditional behaviour all add more moving parts.

### Success Without Required-Field Errors
If an empty form shows a success message, check the submission's spam status and spam logs. Missing honeypot or timing inputs can cause the request to be rejected as spam. With the spam response set to show success, that rejection deliberately looks successful to the visitor; it does not mean the required fields passed validation.

Check that the inputs required by your enabled spam protections are present. Include Formie's assets and the field validation hooks when using its browser validation. Test an empty submission for required-field errors, then a valid submission for normal completion.

Use `form.getFrontendErrorMessage()` for the `data-formie-error-message` attribute, as shown in the example.

### Client-Side Validation and Captchas

Custom forms still need Formie's browser layer for Ajax submits and captchas. Output `craft.formie.formAssets(form)` (or the CSS/JS split) and include the required hidden inputs — especially `submitAction`, `handle`, `renderId`, and `requestToken`.

To disable Formie's client-side validation on submit, turn off **Validate Form on Submit** in the form's settings (under **Behavior**), or set `data-formie-validation-on-submit="false"` on the `<form>`. Captchas still run in the submit pipeline, and accepted requests still undergo server-side field validation. If you want native browser validation, your custom inputs also need the appropriate HTML constraints.

If Ajax submit fails with a generic error when fields are empty, either enable **Validate Form on Submit** so users see field errors before the request is sent, or ensure your template surfaces server validation errors from the returned submission.

See [reCAPTCHA](/integrations/captchas/recaptcha) for cookie-consent deferral with `initJs: false`.

### What's Not Covered
Whilst we've covered the basics, there's still plenty left to address, such as different completion behaviors, multi-page navigation, save-and-resume flows, and payment-specific behaviour. For more complete examples, consult the templates on [Formie's GitHub](https://github.com/verbb/formie/tree/craft-5/src/templates/_special/form-template).

:::tip
Check out the raw templates on [Formie's GitHub](https://github.com/verbb/formie/tree/craft-5/src/templates/_special/form-template) for the most up to date reference.
:::
