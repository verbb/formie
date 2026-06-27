# Cached forms in production

Statically cached pages can serve form HTML with stale CSRF tokens, request tokens, and render IDs — which produces random-looking submit failures. Formie refreshes those values automatically when static-cache support is enabled. This guide covers production setups for Twig-rendered forms, Blitz, Craft Cloud, and headless React/Vue apps.

## Prerequisites

- [Cached Forms](/frontend/cached-forms) reference
- [Configuration](/get-started/configuration) — `staticCacheRefreshOnLoad` and related settings

## Why cached forms fail without refresh

When a page is served from full-page cache, the HTML may be minutes or hours old. Form submissions depend on request-specific values:

- Craft CSRF token
- Formie `requestToken` (duplicate-submit and replay protection)
- Formie `renderId` (form instance identity)

If any of these are stale, submissions fail in ways that look intermittent — especially on first load after cache warm, or after a user leaves the tab open.

## Enable automatic refresh

Formie marks forms as cache-aware and refreshes tokens on initialization, then again after submit attempts.

**Blitz** — refresh is enabled automatically when Blitz is installed and enabled. No extra config required.

**Other static caches** — enable the plugin setting:

```php [config/formie.php]
return [
    '*' => [
        'staticCacheRefreshOnLoad' => true,
    ],
];
```

In most Twig setups, that is all you need. Formie calls the refresh endpoint on load and patches the existing form DOM.

## Craft Cloud and async CSRF

On Craft Cloud and other full-page caches, a synchronous CSRF token in the rendered HTML can prevent the page from being cache-eligible — even if Formie would refresh tokens later.

Enable async CSRF globally:

```php [config/general.php]
return [
    'asyncCsrfInputs' => true,
];
```

Or via environment: `CRAFT_ASYNC_CSRF_INPUTS=true`.

Formie uses Craft's `csrfInput()` helper, so the global setting applies automatically.

### Per-form render options

When you need per-form control instead of a global async CSRF setting:

```twig
{{ craft.formie.renderForm('contactForm', {
    csrfInput: { async: true },
}) }}
```

Or omit the CSRF input entirely and let Formie inject it during token refresh:

```twig
{{ craft.formie.renderForm('contactForm', {
    csrfInput: false,
}) }}
```

Use `csrfInput: false` only when static-cache token refresh is enabled. Formie creates the hidden input on refresh if one is not already present.

## What refresh returns

The refresh payload looks like this:

```json
{
  "csrf": {
    "param": "CRAFT_CSRF_TOKEN",
    "token": "3YV0bKqQx..."
  },
  "requestToken": "m1f8A2pQ...",
  "renderId": "formie-contact-form-abc123"
}
```

Formie applies those values back onto the existing form. The cached HTML keeps working without a full re-render.

## Headless and BYO bundle setups

When you mount forms through `@verbb/formie-react`, `@verbb/formie-vue`, or `@verbb/formie-browser` directly, pass static-cache options at mount time:

```tsx
<FormieForm
    transport="rest"
    endpoint="https://your-craft-site.test"
    formHandle="contactForm"
    staticCache
    refreshTokens
/>
```

If you take over Formie's browser assets entirely (manual bundle, custom mount), keep token refresh enabled in your mount options. Disabling refresh on a statically cached page will reproduce the stale-token failures.

GraphQL headless apps bootstrap via `formieClientForm` and refresh via `refreshFormieClientSession`. The front-end packages call these automatically when `refreshTokens` is true.

## Rate limits on anonymous refresh

Formie rate-limits anonymous bootstrap and token-refresh requests to protect against abuse:

| Setting | Default |
| --- | --- |
| `anonymousClientBootstrapRateLimit` | 30 per window |
| `anonymousClientRefreshRateLimit` | 120 per window |
| `anonymousClientRateWindowSeconds` | 60 |

Set a limit to `0` to disable it. High-traffic cached pages rarely hit these limits because refresh runs once per form mount, not per page view — but load tests on a single IP can.

## Production checklist

1. **Identify your cache layer** — Blitz, Craft Cloud, CDN, or custom static HTML.
2. **Enable refresh** — auto for Blitz; `staticCacheRefreshOnLoad` otherwise.
3. **Fix CSRF cache eligibility** — `asyncCsrfInputs` or `csrfInput: false` on Craft Cloud.
4. **Verify in browser** — load a cached page, inspect network for the refresh request, submit the form.
5. **Headless apps** — pass `staticCache` and `refreshTokens` to framework components.
6. **After deploy** — confirm captchas and payment fields still initialize post-refresh (they receive updated module config in the session payload).

## When refresh is not enough

If you customize save/resume, multi-page Ajax, or SPA navigation (Barba, Sprig, Datastar), you may need to re-mount Formie when the form DOM is replaced. The starters include examples:

- [Barba.js example](https://formie-starters.verbb.io/barba)
- [Sprig example](https://formie-starters.verbb.io/sprig)
- [Datastar example](https://formie-starters.verbb.io/datastar)

The pattern is the same: unmount the old instance, mount fresh on the new DOM, and let refresh run on the new mount.

## Related

- [Cached Forms](/frontend/cached-forms)
- [Configuration](/get-started/configuration)
- [Building a headless contact form with Formie React](/guides/frontend-headless/building-a-headless-contact-form-with-formie-react)
- [GraphQL submission flow end-to-end](/guides/frontend-headless/graphql-submission-flow-end-to-end)
