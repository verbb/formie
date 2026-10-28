# Render Options

Render options are the shared settings you can pass to Formie's render helpers, such as `renderForm()`, `renderPage()`, and `renderField()`.

<span id="sessionkey"></span>

## Draft Contexts

Use a draft context when the same form appears for different entries, products or events and each should keep its in-progress answers separate. Set it on the form with `setDraftContext()` before populating values or rendering; it is not a `renderForm()` option.

By default, Formie separates a visitor’s progress by form, site and URL path. Query parameters are not part of that default context, so `/booking?trip=3333` and `/booking?trip=4444` share a context. Set an explicit context based on the entry ID to keep those bookings separate.

In this Twig example, `target` is the trip entry already loaded by your template. The `booking` form has a Single-Line Text field with the handle `tripTitle`:

```twig
{% set form = craft.formie.forms.handle('booking').one() %}

{% do form.setDraftContext('booking_' ~ target.id) %}

{% do craft.formie.populateFormValues(form, {
    tripTitle: target.title,
}, true) %}

{{ craft.formie.renderForm(form) }}
```

Keep the context stable for the same trip. With automatic restoration enabled, visiting another trip starts or restores that trip’s booking; returning to the original trip restores its own progress. Set the context before `populateFormValues()` because population can load an existing submission. Changing populated values alone does not separate drafts.

The final `true` forces the trip title to come from the template across pages and resumed submissions. Omit it if the value should only be an editable starting value; see [Populating Forms](/templates/populating-forms#value-precedence). Pass presentation options such as `themeConfig` to `renderForm()` as usual. Formie carries the draft context through subsequent form requests without manual encoding.

For existing templates, see [Scoping In-Progress Submissions](/get-started/upgrading-from-v3#scoping-in-progress-submissions) in the upgrade guide.

### Automatic Restoration

In the form builder, open **Behaviour → Advanced → Restore In-Progress Submissions Automatically** (`automaticSubmissionState`). This is enabled by default. Leave it enabled when visitors should return to the same draft context and continue their booking.

Turn it off if each normal revisit should start fresh. This disables automatic restoration, but multi-page progression still works during the current attempt, incomplete submissions are still saved, and explicit resume links still work. See [Save and Continue Later](/forms/save-continue-later) for how those links work.

## Core Options

### `fieldNamespace`

`fieldNamespace` changes the namespace used for field names. The default is `fields`, which is what you usually want.

Change it when the receiving action expects raw field names instead of the default `fields[...]` structure. That is most common when working with [Craft Forms](/templates/craft-forms).

> [!WARNING]
> Only change `fieldNamespace` if you know exactly how the receiving request is being handled. Using the wrong namespace can stop a form from submitting or prevent Formie from reading field values correctly, so this is best treated as an advanced option.

```twig
{{ craft.formie.renderForm('contactForm', {
    fieldNamespace: '',
}) }}
```

### `themeConfig`

`themeConfig` lets you apply theme config at render time instead of changing the form's saved theme settings.

This is useful when a form should inherit different presentation in different templates, or when you want to tweak classes and attributes without creating a separate form just for styling changes.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        form: {
            attributes: {
                class: ['formie-theme-compact'],
            },
        },
    },
}) }}
```

For the full structure and available options, see [Theme Config](/theming/theme-config).

`themeConfig` applies to this rendered form without changing its saved settings. You can render the same form with different themes on the same page. Browser-supplied config supports styling only; use PHP, Twig or [Template Overrides](/theming/template-overrides) to change markup.

### `customInputs`

`customInputs` adds hidden inputs to the rendered form output.

Use it when the form request needs extra values that do not belong to visible fields.

```twig
{{ craft.formie.renderForm('contactForm', {
    customInputs: {
        campaign: 'spring-launch',
        sourcePage: entry.slug,
    },
}) }}
```

That would render hidden inputs into the form output, similar to:

```html
<form method="post" accept-charset="UTF-8">
    ...
    <input type="hidden" name="campaign" value="spring-launch">
    <input type="hidden" name="sourcePage" value="about-us">
    ...
</form>
```

### `csrfInput`

`csrfInput` controls how Craft's CSRF hidden input is rendered inside the form.

By default, Formie outputs `csrfInput({ autocomplete: 'off' })`. That respects Craft's global `asyncCsrfInputs` setting when enabled.

Use this when a statically cached page needs per-form control — for example, Craft Cloud setups where a synchronous CSRF token would prevent the page from being cached.

```twig
{# default — unchanged #}
{{ craft.formie.renderForm('contactForm') }}

{# per-form async CSRF without a global Craft config change #}
{{ craft.formie.renderForm('contactForm', {
    csrfInput: { async: true },
}) }}

{# omit the CSRF input — Formie injects it on token refresh before submit #}
{{ craft.formie.renderForm('contactForm', {
    csrfInput: false,
}) }}
```

When `csrfInput` is `false`, enable [cached form token refresh](/frontend/cached-forms) so Formie can inject a fresh CSRF input on initialisation.

### `templateVars`

`templateVars` passes extra values into the rendering context for template overrides and custom rendering.

This is most useful when your Twig templates need a small amount of extra context at render time.

```twig
{{ craft.formie.renderForm('contactForm', {
    templateVars: {
        themeVariant: 'compact',
    },
}) }}
```

## Asset Output

### `outputCss`

`outputCss` controls whether Formie should output its front-end CSS when the form is rendered.

This is most useful when your layout already handles CSS separately, or when you want to disable Formie's CSS in a specific context.

```twig
{{ craft.formie.renderForm(form, {
    outputCss: false,
}) }}
```

### `outputJs`

`outputJs` controls whether Formie should output its front-end JavaScript when the form is rendered.

This is most useful when your layout already handles JavaScript separately, or when you want more deliberate control over when Formie's browser behaviour loads.

```twig
{{ craft.formie.renderForm(form, {
    outputJs: true,
}) }}
```

### `outputCssLocation`

`outputCssLocation` controls where Formie places its generated CSS output.

The available values are:

- `page-header`
- `inside-form`
- `manual`

Use this when your template layout needs the CSS to land somewhere specific, rather than wherever the form is rendered.

### `outputJsLocation`

`outputJsLocation` controls where Formie places its generated JavaScript output.

The available values are:

- `page-footer`
- `inside-form`
- `manual`

Use this when your template layout needs the JavaScript to land somewhere specific, rather than wherever the form is rendered.

### `cssAttributes`

`cssAttributes` lets you add attributes to the generated CSS tags.

That can be useful for media attributes or other front-end requirements.

```twig
{{ craft.formie.renderForm(form, {
    cssAttributes: {
        media: 'print',
    },
}) }}
```

That would affect the generated CSS tag, for example:

```html
<link rel="stylesheet" href="/dist/formie.css" media="print">
```

### `scriptAttributes`

`scriptAttributes` lets you add attributes to generated script tags.

This is useful for CSP nonces and other script-level requirements.

```twig
{{ craft.formie.renderForm(form, {
    scriptAttributes: {
        nonce: cspNonce,
        defer: true,
    },
}) }}
```

That would affect the generated script tag, for example:

```html
<script type="module" src="/dist/formie.js" nonce="abc123" defer></script>
```

### `jsAttributes`

`jsAttributes` also lets you add attributes to generated script tags.

It can be used in the same way as `scriptAttributes`, and is supported as a JavaScript-specific alias.

```twig
{{ craft.formie.renderForm(form, {
    jsAttributes: {
        crossorigin: 'anonymous',
    },
}) }}
```

```html
<script type="module" src="/dist/formie.js" crossorigin="anonymous"></script>
```

<span id="initialization"></span>

## Initialisation

### `initJs`

`initJs` turns automatic browser initialisation on or off for the rendered form.

This is mainly useful when you are controlling initialisation yourself.

```twig
{{ craft.formie.renderForm(form, {
    initJs: false,
}) }}
```

Use this when you need to delay Formie initialisation — for example until cookie consent is granted for captcha scripts. See [reCAPTCHA — Cookie consent and deferred loading](/integrations/captchas/recaptcha#cookie-consent-and-deferred-loading).

### `useObserver`

`useObserver` controls whether Formie's browser script should automatically initialise new forms added to the page later.

This matters most on pages where forms are injected after load, such as modal, Ajax, or front-end application workflows.

```twig
{{ craft.formie.renderForm(form, {
    useObserver: false,
}) }}
```
