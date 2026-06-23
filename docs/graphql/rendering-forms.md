# Rendering Forms

Formie has GraphQL queries for projects that want Formie to render the form HTML, or for front-end packages that need a structured form payload.

Most sites that are already rendering Twig templates should use the template functions covered in [Rendering Forms](/templates/rendering-forms). Use these GraphQL queries when your front-end is loading forms through GraphQL.

## Rendered HTML

Use `formieHtmlForm` when you want Formie to return rendered form HTML.

```graphql
query FormHtml($handle: String!, $siteId: Int, $input: ServerRenderPayloadInput) {
    formieHtmlForm(handle: $handle, siteId: $siteId, input: $input) {
        html
    }
}
```

```json
{
    "handle": "contactForm",
    "siteId": 1,
    "input": {
        "theme": "default",
        "locale": "en",
        "siteId": 1
    }
}
```

The query accepts a required `handle`, an optional top-level `siteId`, and an optional `input` object.

The `input` argument can include:

Argument | Type | Description
--- | --- | ---
`theme` | `String` | The form template theme handle.
`themeConfig` | `Json` | Theme config overrides for this render.
`locale` | `String` | The locale to use for the rendered form.
`siteId` | `Int` | The site ID to render the form for.

If both top-level `siteId` and `input.siteId` are provided, Formie uses the `input.siteId` value for the render request.

The HTML payload does not include the normal CSS and JavaScript asset tags. If you render form HTML this way, make sure your front-end includes the browser assets covered in [Frontend Assets](/frontend/frontend-assets).

## Front-End Package Payload

Use `formieClientForm` when a Formie front-end package needs the structured form definition and session payload.

```graphql
query FormieForm($handle: String!, $siteId: Int, $locale: String) {
    formieClientForm(handle: $handle, siteId: $siteId, locale: $locale) {
        schemaVersion
        definition
        session
    }
}
```

This query is intended for Formie’s React, Vue and Web Component packages. The package docs are the better place for implementation details because they show the full front-end flow, including submitting, page changes and session refreshes.

## Getting A CSRF Token

If you are building your own GraphQL-driven front-end, a common first step is getting the CSRF token and other request tokens needed for later interactions. Query `formieClientForm`, then read the values from `session.tokens`.

```graphql
query FormieSessionTokens($handle: String!, $siteId: Int, $locale: String) {
    formieClientForm(handle: $handle, siteId: $siteId, locale: $locale) {
        session
    }
}
```

```json
{
    "data": {
        "formieClientForm": {
            "session": {
                "id": "formie-contactForm-abc123",
                "currentPageId": "page-1",
                "tokens": {
                    "csrf": {
                        "name": "CRAFT_CSRF_TOKEN",
                        "value": "3YV0bKqQx..."
                    },
                    "request": "m1f8A2pQ...",
                    "render": "formie-contactForm-abc123",
                    "captchas": {}
                }
            }
        }
    }
}
```

The `csrf` object gives you the hidden input name and value for Craft’s CSRF protection. The same `tokens` payload also includes the request and render tokens that Formie uses for duplicate-submit protection, progression, and refresh flows.

If you are using `formieHtmlForm` instead, Formie renders the normal hidden inputs into the returned HTML for you, so you usually do not need to fetch the CSRF token separately.

The matching mutations are:

Mutation | Purpose
--- | ---
`submitFormieClientForm` | Submits a structured form payload.
`refreshFormieClientSession` | Refreshes the form session payload.
`setFormieClientPage` | Saves page navigation state for multi-page forms.

Forms with Payment fields should use `submitFormieClientForm` in headless front-ends. See [Headless Payments](/graphql/payments).
