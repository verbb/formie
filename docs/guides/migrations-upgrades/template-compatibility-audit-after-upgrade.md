# Template compatibility audit after upgrade

After a major upgrade, most regressions show up in Twig templates and front-end JavaScript — renamed render helpers, asset flags, submission value methods, and DOM event names. This audit explains what changed and how to find remaining call sites before production cutover.

## Prerequisites

- Formie installed on staging with `compatibilityMode` enabled
- [Upgrading From v3](/get-started/upgrading-from-v3) for context
- [Rendering Forms](/templates/rendering-forms) and [Frontend Assets](/frontend/frontend-assets)

## Step 1 — Find deprecated calls with Craft Deprecations

With `compatibilityMode` enabled (the default), Formie keeps legacy Twig helpers, render options, and submission value methods working — but Craft logs a deprecation warning each time one is used.

1. On staging, clear the baseline: **Craft → Utilities → Deprecations → Clear All Deprecation Warnings**, or run `./craft clear-deprecations` from the terminal.
2. Browse the site and submit forms on every template that renders Formie output — page reload, Ajax, and multi-page at minimum.
3. Open **Craft → Utilities → Deprecations** and search for `formie` or `verbb\formie`.
4. Each entry shows the deprecated call, its replacement, and the file and line that triggered it. Work through that list as your fix queue.

If a template is not hit during manual testing, its deprecation will not appear yet. Use the [manual page verification](#step-10--manual-page-verification) checklist to make sure you exercise every form template, then re-check the Deprecations utility.

The same warnings are written to `storage/logs/web.log` if you prefer tailing logs — the Utilities screen is usually faster to scan.

The sections below are a reference for the most common template and front-end changes you will see in that list.

## Step 2 — Twig render API updates

### Form assets

| Previous | Current |
| --- | --- |
| `craft.formie.renderFormAssets(form)` | `craft.formie.formAssets(form)` |
| `craft.formie.registerFormAssets(form)` | `craft.formie.formAssets(form)` |
| `craft.formie.renderFormCss(form)` | `craft.formie.formAssets(form, { includeJs: false })` |
| `craft.formie.renderFormJs(form)` | `craft.formie.formAssets(form, { includeCss: false })` |
| `craft.formie.renderRuntimeAssets()` | `craft.formie.frontendAssets()` |

### Render options on renderForm

| Old option | New option |
| --- | --- |
| `renderCss: false` | `includeCss: false` |
| `renderJs: false` | `includeJs: false` |
| `outputCssLayout` / `outputCssTheme` | `outputCss` |
| `outputJsBase` / `outputJsTheme` | `outputJs` |

Example:

```twig
{# Before #}
{{ craft.formie.renderForm('contact', {
    renderCss: false,
    renderJs: true,
}) }}

{# After #}
{{ craft.formie.renderForm('contact', {
    includeCss: false,
    includeJs: true,
}) }}
```

Output location options (`outputCssLocation`, `outputJsLocation`) are unchanged — see [Render Options](/templates/render-options).

## Step 3 — Render ID

```twig
{# Before #}
{% set id = form.getFormId() %}

{# After #}
{% set id = form.getRenderId() %}
```

Update `#{{ id }}` selectors in `{% js %}` blocks and analytics snippets.

## Step 4 — Submission values in Twig

```twig
{# Before #}
{{ submission.getValueAsString('fullName') }}
{% set address = submission.getValueAsJson('billingAddress') %}

{# After #}
{{ submission.getFieldValueAsString('fullName') }}
{% set address = submission.getFieldValueAsArray('billingAddress') %}
```

Use the helper that matches the job:

- `getFieldValue()` — natural shape
- `getFieldValueAsString()` — display, logs
- `getFieldValueAsArray()` — structured fields
- `getFieldValueForExport()` — CSV/reports
- `getFieldValueForSummary()` — review screens

## Step 5 — Front-end JavaScript events

| Previous | Current |
| --- | --- |
| `onFormieLoaded` / `onFormieReady` | `formie:mount:after` |
| `onBeforeFormieSubmit` | `formie:submit:before` |
| `onAfterFormieSubmit` | `formie:submit:result` |
| `onFormiePageToggle` | `formie:page:navigate:after` |
| `formieValidatorInitialized` | `formie:validator:ready` |

Example migration:

```js
// Before
form.addEventListener('onAfterFormieSubmit', (event) => { … });

// After
form?.addEventListener('formie:submit:result', (event) => {
    const result = event.detail;
    if (!result.ok || result.nextPage) return;
    // final success only
});
```

Temporary bridge (remove after migration):

```html
<form data-formie data-formie-form data-formie-compatibility="true">
```

Or `compatibility: true` in `createFormieClient().mount()`.

## Step 6 — Custom validators

```js
// Before
form.addEventListener('onFormieThemeReady', (event) => {
    event.detail.addValidator(…);
});

// After
form?.addEventListener('formie:validator:ready', (event) => {
    const { validator } = event.detail;
    validator.addValidator(…);
});
```

## Step 7 — Static cache snippets

Remove any custom token-refresh JavaScript from templates — Formie handles static-cache token refresh when enabled. Enable:

```php
'staticCacheRefreshOnLoad' => true,
```

And on Craft Cloud, async CSRF — see [Cached forms in production](/guides/frontend-headless/cached-forms-in-production).

Delete obsolete Blitz/Formie refresh snippets from templates after verifying submit works on cached pages.

## Step 8 — GraphQL clients

| Old query field | New |
| --- | --- |
| `formieForm { templateHtml }` | `formieHtmlForm { html }` |
| `enableJsEvents` | `enableClientEvents` |
| `jsGtmEventOptions` | `clientEventFields` |

Headless apps should bootstrap via `formieClientForm` and submit via `submitFormieClientForm`.

## Step 9 — Theme config and CSS classes

Default theme prefix is `formie`, not `fui`. Deprecation logging will not catch stale CSS selectors — review custom stylesheets for `.fui-` rules and update to `.formie-` or CSS variables on `.formie-form`.

Custom fields using Theme Config should use `defineFieldSlotTag()`.

## Step 10 — Manual page verification

For each template that renders a form:

- Form displays with styles
- Validation errors show
- Multi-page next/back works (if applicable)
- Success message or redirect works
- Analytics / GTM fires on final submit only
- No console errors from Formie browser package

Test one **page-reload** form and one **Ajax** form at minimum.

## Step 11 — Confirm deprecations are clear

After fixing everything flagged in Step 1:

1. Clear deprecations again: **Utilities → Deprecations → Clear All Deprecation Warnings**, or `./craft clear-deprecations`.
2. Re-run the manual page verification checklist above.
3. **Utilities → Deprecations** should show no Formie entries.
4. Disable `compatibilityMode` only when that list stays empty after realistic staging traffic.

Optional proactive pass: if you want to search the codebase before exercising every page, look in `templates/` and front-end `src/` for `renderFormAssets`, `getFormId`, `getValueAsString`, and `onAfterFormieSubmit`. The Deprecations utility remains the authoritative checklist once your templates are actually rendered.

## Related

- [Upgrading From v3](/get-started/upgrading-from-v3)
- [Major upgrade — real-project checklist](/guides/migrations-upgrades/major-upgrade-real-project-checklist)
- [Rendering Forms](/templates/rendering-forms)
- [Frontend Assets](/frontend/frontend-assets)
- [Cached forms in production](/guides/frontend-headless/cached-forms-in-production)
