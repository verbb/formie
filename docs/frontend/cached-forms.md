# Cached Forms

Cached forms work well, but they need fresh request-specific values.

When a page is served from static cache, the HTML may be older than the current request. That matters for things like CSRF tokens, request tokens, and render IDs. If those are stale, a form can fail in ways that look random, especially on cached pages using [Blitz](https://putyourlightson.com/plugins/blitz) or another full-page caching layer.

## Automatic Refresh

Formie can handle this for you. When static-cache support is enabled, Formie marks the form as cache-aware and refreshes the tokens it needs when the form is initialized. It also refreshes them again after submit attempts, so retries, page changes, and repeated submissions keep using fresh values.

Static-cache support is enabled automatically when Blitz is installed and enabled. For other static-cache setups, enable the `staticCacheRefreshOnLoad` plugin setting in your [Formie config](/get-started/configuration).

In most cases, Formie can support cached forms without extra JavaScript on the site.

## When You Might Need More Control

If you are taking over Formie's browser assets yourself, or you are mounting forms through your own browser bundle, make sure token refresh stays enabled. The browser package can receive `staticCache` and `refreshTokens` options, but you would normally only set those yourself when you are deliberately replacing Formie's automatic asset output.

If you need to control that setup yourself, see:

- [Frontend Assets](/frontend/frontend-assets)
- [Render Options](/templates/render-options)
- [Assets](/templates/assets)

## How It Works

On load, Formie can send a request to the refresh tokens endpoint and update the form with fresh values before the user submits anything.

After submit attempts, it can refresh them again so the form is ready for the next page submit, retry, or repeated interaction.

The refresh payload can look like this:

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

Formie applies those values back onto the existing form, so the cached HTML can keep working without needing a full re-render.
