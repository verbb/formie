# Rendering Pages

Render pages manually when you want control over the outer `<form>` element but still want Formie to render each page.

This sits in the middle ground between full automatic rendering and fully custom rendering.

Start with the complete form example in [Custom Rendering](/theming/custom-rendering), which includes the browser attributes, routing inputs and spam-protection inputs. For a single-page form, replace its field loop and submit button with:

```twig
{{ craft.formie.renderPage(form) }}
```

The page renderer supplies its fields, captchas and buttons. For multi-page forms, use `renderForm()` unless your custom template also handles page navigation and submission state.

## What You Still Need

Once you stop using `renderForm()`, you become responsible for the outer form setup, hidden inputs, assets and submission feedback. Formie’s browser startup discovers `data-formie` or `data-formie-form` on the outer form and reads the accompanying `data-formie-*` attributes; the full setup is shown in [Custom Rendering](/theming/custom-rendering).

Forms that post to another Craft action use a different setup. Follow [Craft Forms](/templates/craft-forms) for login, registration and profile forms, including their captcha limitations.

## Assets

Use `craft.formie.formAssets(form)` before the form markup when you are assembling the form yourself.

That supplies the browser CSS, startup script and translations. It does not add attributes or hidden inputs to your form.

See [Assets](/templates/assets) for the available helpers.

## Render Options

`renderPage()` accepts the same shared render options as `renderForm()`.

```twig
{{ craft.formie.renderPage(form, page, {
    themeConfig: {
        page: {
            attributes: {
                class: 'my-page',
            },
        },
    },
}) }}
```

Pass `fieldNamespace: ''` to render root input names without `fields[...]`, or provide another namespace. Nested fields retain their Group and Repeater paths. The option is scoped to the render call.
