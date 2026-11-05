# Assets

Most of the time, `renderForm()` handles assets for you.

You only need to think about assets directly when you are rendering pages or fields manually, [loading forms dynamically](#dynamically-loaded-forms), or working with Formie's browser layer more explicitly.

`formie.css` includes both the base CSS needed for form behaviour and Formie's visual theme. Choosing the `none` theme keeps the base CSS and JavaScript while leaving visual styling to you. See [Front-End Assets](/frontend/frontend-assets) for the separate CSS files.

## `craft.formie.formAssets(form)`

Use `formAssets()` when you are rendering a specific form manually and still want Formie's browser assets resolved in the right way for that form.

```twig
{% set form = craft.formie.forms.handle('contactForm').one() %}

{{ craft.formie.formAssets(form) }}
```

This is the normal helper to use with [Rendering Pages](/templates/rendering-pages) and [Rendering Fields](/templates/rendering-fields).

## `craft.formie.browserAssets()`

Use `browserAssets()` when you need the browser assets without resolving a specific form.

```twig
{{ craft.formie.browserAssets() }}
```

This is more of an advanced helper. Most template-driven sites will use `formAssets(form)` instead.

## Dynamically Loaded Forms

If your site loads a form into a modal or replaces page content using Ajax, load Formie's shared CSS and JavaScript on the initial page. Formie can then watch for forms added to the document and initialise them automatically, even when the initial page has no forms.

Add this once inside the `<head>` of your main layout, outside the content your site replaces:

```twig
{{ craft.formie.browserAssets({
    inline: true,
    useObserver: true,
}) }}
```

`inline: true` outputs the stylesheet and script tags where you call the helper. The files are still loaded from their URLs; their contents are not embedded in the HTML. Without this option, Craft registers the tags for the page's head or footer, which may be missing from a fragment response or discarded by your content loader.

`useObserver: true` enables automatic initialisation when forms are inserted into the document. This is the default, but the example includes it explicitly. You don't need to query every form or load the shared assets separately for each one. Formie loads additional field and integration modules as needed.

In the Twig template that returns your dynamically loaded content, render the form with asset output disabled and initialisation enabled. This example assumes you have a form with the handle `contactForm`; replace that handle with your own:

```twig
{{ craft.formie.renderForm('contactForm', {
    outputCss: false,
    outputJs: false,
    initJs: true,
}) }}
```

Insert the returned form HTML into the document using your site's existing content loader. The shared styles are already available, and Formie initialises the form's browser behaviour automatically. You don't need to execute script tags from the fragment or call a separate initialisation method.

To check the setup, start on a page without a form and load the content containing `contactForm`. The form should have its styling and browser behaviour, such as required-field validation. If it stays unstyled, check that the initial page includes the stylesheet tags. If its browser behaviour is missing, check that the startup script loaded, `useObserver` is enabled, and the inserted form has not opted out with `initJs: false`.

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
{{ craft.formie.browserAssets({
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
- you want finer control over browser initialisation

When Formie outputs the browser JavaScript for you, it also outputs:

- the startup module script with page-level settings such as `useObserver`
- an inline JSON translations seed for front-end validation and UI messages

`initJs` is not an asset-level toggle. It belongs on the rendered form markup, such as `renderForm(form, { initJs: false })`, where it opts that specific form out of automatic startup.

`useObserver` is the page-level browser-script toggle. You can pass it through whichever render path is outputting the JavaScript for the page, such as `renderForm()`, `formAssets()`, or `browserAssets()`.

If you are rendering one form manually, reach for `formAssets(form)`. If you are wiring up Formie's browser assets yourself, reach for `browserAssets()`.
