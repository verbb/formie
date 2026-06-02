# Render Options

Render options are the shared settings you can pass to Formie's render helpers, such as `renderForm()`, `renderPage()`, and `renderField()`.

## Core options

### `sessionKey`

Use `sessionKey` when the same form can appear in more than one context and each instance should keep its saved progress separate.

Formie still uses this when building the identity for in-progress submission state. That makes it useful for forms that are repeated across entries, products, events, or other content where each instance should behave independently.

```twig
{{ craft.formie.renderForm('registration', {
    sessionKey: entry.id,
}) }}
```

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

## Asset output

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

This is most useful when your layout already handles JavaScript separately, or when you want more deliberate control over when Formie's browser behavior loads.

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

## Initialization

### `initJs`

`initJs` turns automatic browser initialization on or off for the rendered form.

This is mainly useful when you are controlling initialization yourself.

```twig
{{ craft.formie.renderForm(form, {
    initJs: false,
}) }}
```

### `useObserver`

`useObserver` controls whether Formie's browser script should automatically initialize new forms added to the page later.

This matters most on pages where forms are injected after load, such as modal, Ajax, or front-end application workflows.

```twig
{{ craft.formie.renderForm(form, {
    useObserver: false,
}) }}
```
