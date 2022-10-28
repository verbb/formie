# Available Variables

The following Twig functions are available to call in your Twig templates.

### `craft.formie.forms(query)`
See [Form Queries](docs:getting-elements/form-queries)

```twig
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}
```


### `craft.formie.submissions(query)`
See [Submission Queries](docs:getting-elements/submission-queries)

```twig
{% set submissions = craft.formie.submissions({ formId: 123, limit: 10 }).all() %}
```


### `craft.formie.renderForm(form, options = {})`
Renders the entire form, taking into account custom [Form Templates](docs:feature-tour/form-templates). The `form` parameter can be a [Form](docs:developers/form) object, or the handle of a form. For details on the options you can pass into the `options` parameter, see [Render Options](docs:theming/render-options).

```twig
{{ craft.formie.renderForm('contactForm') }}
```


### `craft.formie.renderPage(form, page)`
Renders a single page, taking into account custom [Form Templates](docs:feature-tour/form-templates). This will also include captchas (if enabled) and submit button(s).

```twig
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}

{% for page in form.getPages() %}
    {{ craft.formie.renderPage(form, page) }}
{% endfor %}
```


### `craft.formie.renderField(form, field)`
Renders a single field, taking into account custom [Form Templates](docs:feature-tour/form-templates).

```twig
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}

{% for field in form.getCustomFields() %}
    {{ craft.formie.renderField(form, field) }}
{% endfor %}
```


### `craft.formie.registerAssets(form, options = {})`
Used to specifically register the CSS and JS for a form. Takes the same arguments as `craft.formie.renderForm`. This is particular useful for [Cached Forms](docs:template-guides/cached-forms) or [Custom Rendering](docs:theming/custom-rendering).

```twig
{% do craft.formie.registerAssets('contactForm') %}
```


### `craft.formie.renderFormCss(form)`
Allows specific placement of a form's CSS on the page. Takes the same arguments as `craft.formie.renderForm`

```twig
{{ craft.formie.renderFormCss('contactForm') }}

<div class="form-wrap">
    {{ craft.formie.renderForm('contactForm') }}
</div>
```


### `craft.formie.renderFormJs(form)`
Allows specific placement of a form's JS on the page. Takes the same arguments as `craft.formie.renderForm`

```twig
<div class="form-wrap">
    {{ craft.formie.renderForm('contactForm') }}
</div>

{{ craft.formie.renderFormJs('contactForm') }}
```


### `craft.formie.getFieldOptions(field)`
Returns a field's render options from the main options array.


### `craft.formie.getLabelPosition(field)`
Returns the label position for a field.

```twig
{% set labelPosition = craft.formie.getLabelPosition(field, form) %}

{% if labelPosition.shouldDisplay('above') %}
    <label>...</label>
    ...
```


### `craft.formie.getLabelPosition(field)`
Returns the label position for a field.

```twig
{% set labelPosition = craft.formie.getLabelPosition(field, form) %}

{% if labelPosition.shouldDisplay('above') %}
    <label>...</label>
    ...
```


### `craft.formie.getInstructionsPosition(field)`
Returns the instructions position for a field.

```twig
{% set instructionsPosition = craft.formie.getInstructionsPosition(field, form) %}

{% if instructionsPosition.shouldDisplay('below') %}
    <small>...</small>
    ...
```


### `craft.formie.getParsedValue(value, submission)`
For parsing a variable-string against a submission. For example, you might have a field with a handle `emailAddress`, and you could use the string `{emailAddress}` and a supplied submission to swap the template tag with the content from a submission.

```twig
{% set toEmail = craft.formie.getParsedValue('Send me an email to {emailAddress}', submission) %}
```


### `craft.formie.setCurrentSubmission(form, submission)`
Sets the current submission for a particular form. This is mostly used for multi-page forms.


### `craft.formie.populateFormValues(handle, values, force = false)`
Populates the `defaultValue` of a field for a form. Note that this should be done before rendering the form. See [Populating Forms](docs:template-guides/populating-forms).

```twig
{# Sets the field with handle `text` to "Some Value" for the form with a handle `contactForm` #}
{% do craft.formie.populateFormValues('contactForm', { text: "Some Value" }) %}

{# or use a `form` object #}
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}
{% do craft.formie.populateFormValues(form, { text: "Some Value" }) %}

{# Must be done before the `renderForm()` #}
{{ craft.formie.renderForm('contactForm') }}
```
