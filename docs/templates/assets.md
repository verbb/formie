# Assets

Most of the time, `renderForm()` handles assets for you.

You only need to think about assets directly when you are rendering pages or fields manually, or when you are working with Formie's browser layer more explicitly.

## `craft.formie.formAssets(form)`

Use `formAssets()` when you are rendering a specific form manually and still want Formie's browser assets resolved in the right way for that form.

```twig
{% set form = craft.formie.forms.handle('contactForm').one() %}

{{ craft.formie.formAssets(form) }}
```

This is the normal helper to use with [Rendering Pages](/templates/rendering-pages) and [Rendering Fields](/templates/rendering-fields).

## `craft.formie.frontendAssets()`

Use `frontendAssets()` when you need the browser assets without resolving a specific form.

```twig
{{ craft.formie.frontendAssets() }}
```

This is more of an advanced helper. Most template-driven sites will use `formAssets(form)` instead.

## Options

These helpers can accept options such as:

- `includeCss`
- `includeJs`
- `inline`
- `cssAttributes`
- `scriptAttributes`
- `jsAttributes`
- `useObserver`

For example:

```twig
{{ craft.formie.frontendAssets({
    includeCss: false,
    includeJs: true,
    scriptAttributes: {
        nonce: cspNonce,
    },
    useObserver: false,
}) }}
```

That is mainly useful when:

- your CSP requires extra script attributes
- you want to suppress CSS or JS in a specific context
- you want finer control over browser initialization

When Formie outputs the browser JavaScript for you, it also outputs:

- the startup module script with page-level settings such as `useObserver`
- an inline JSON translations seed for front-end validation and UI messages

`initJs` is not an asset-level toggle. It belongs on the rendered form markup, such as `renderForm(form, { initJs: false })`, where it opts that specific form out of automatic startup.

`useObserver` is the page-level browser-script toggle. You can pass it through whichever render path is outputting the JavaScript for the page, such as `renderForm()`, `formAssets()`, or `frontendAssets()`.

If you are rendering one form manually, reach for `formAssets(form)`. If you are wiring up Formie's front-end assets yourself, reach for `frontendAssets()`.
