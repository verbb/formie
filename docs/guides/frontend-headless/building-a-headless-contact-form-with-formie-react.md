# Building a headless contact form with Formie React

This walkthrough takes you from a Formie contact form in Craft to a working React front end that loads, renders, and submits the form over REST or GraphQL — without Twig templates on the page.

## Prerequisites

- Formie installed on a Craft site with at least one form (for example, handle `contactForm`)
- A React app (Vite, Next.js, or similar)
- [Frontend Assets](/frontend/frontend-assets) — how Formie's browser packages fit together
- [Rendering Forms](/graphql/rendering-forms) and [Create Submissions](/graphql/create-submissions) if you use GraphQL

## Choose your rendering approach

Formie React supports two paths:

| Approach | Component | Best for |
| --- | --- | --- |
| **Server-rendered** | `<FormieForm />` | You want Formie-owned HTML and browser behaviour with minimal React code |
| **Client-rendered** | `<FormieClientForm />` | You own the field markup and styling; Formie provides definition and submission flow |

Both paths share the same transport options: `rest` or `graphql`. Start with server-rendered if you want the fastest path to a working form; move to client-rendered when you need full design control.

## Step 1 — Install packages

```bash
npm install @verbb/formie-browser @verbb/formie-core @verbb/formie-react react react-dom
```

Import Formie's CSS once in your app entry:

```tsx
import '@verbb/formie-browser/css/formie.css';
```

## Step 2 — Configure Craft for headless access

Your React app needs to reach Craft's Formie endpoints.

1. Enable a public GraphQL schema (or REST route) with the Formie scopes your front end needs — at minimum, form queries and submission mutations for the forms you expose.
2. Keep `enableCsrfValidationForGuests` enabled in `config/formie.php` unless you have a specific reason not to. Client transports send the CSRF token from the session payload.
3. If the React app runs on a different origin, configure CORS and keep `allowedGraphqlOrigins` as narrow as practical.

For local development, point your React app at `https://your-craft-site.test` and ensure the dev server can reach Craft.

## Step 3 — Server-rendered contact form (REST)

The quickest headless setup: Formie fetches the form payload, mounts its HTML, and handles validation, conditions, and submission.

```tsx [src/components/ContactForm.tsx]
import { FormieForm } from '@verbb/formie-react';
import '@verbb/formie-browser/css/formie.css';

export function ContactForm() {
    return (
        <FormieForm
            transport="rest"
            endpoint="https://your-craft-site.test"
            formHandle="contactForm"
            theme="formie"
            onSubmitSuccess={(result) => {
                console.log('Submitted:', result);
            }}
        />
    );
}
```

That is enough for a single-page contact form with email validation, captchas, and success messaging configured in the Formie control panel.

### GraphQL transport

Swap `transport="rest"` for `transport="graphql"` and point `endpoint` at your GraphQL URL (for example `https://your-craft-site.test/api`):

```tsx
<FormieForm
    transport="graphql"
    endpoint="https://your-craft-site.test/api"
    formHandle="contactForm"
/>
```

Formie uses `formieClientForm` and `submitFormieClientForm` under the hood. See [GraphQL submission flow end-to-end](/guides/frontend-headless/graphql-submission-flow-end-to-end) for the full query and mutation sequence.

### Pre-fetched payload

If your app already fetched the form payload (for example, during SSR or in a loader), pass it directly:

```tsx
import { FormieForm, type FormEndpointPayload } from '@verbb/formie-react';

export function ContactForm({ payload }: { payload: FormEndpointPayload }) {
    return <FormieForm source={{ payload }} />;
}
```

## Step 4 — Client-rendered contact form

When you need your own field components, use `<FormieClientForm />`. Formie loads the form definition; you render each field.

```tsx
import { FormieClientForm } from '@verbb/formie-react';
import '@verbb/formie-browser/css/formie.css';

export function ContactForm() {
    return (
        <FormieClientForm
            transport="graphql"
            endpoint="https://your-craft-site.test/api"
            formHandle="contactForm"
            components={{
                Field({ field, children, errors }) {
                    return (
                        <div className="field">
                            <label htmlFor={field.id}>{field.label}</label>
                            {children}
                            {errors.map((error) => (
                                <p key={error} className="error">{error}</p>
                            ))}
                        </div>
                    );
                },
                SubmitButton({ label, disabled }) {
                    return (
                        <button type="submit" disabled={disabled}>
                            {label}
                        </button>
                    );
                },
            }}
        />
    );
}
```

Use `useFormie`, `useFormieField`, and `useFormiePage` from `@verbb/formie-react` when you need finer control over layout — for example, a custom multi-column grid or inline validation display.

## Step 5 — Multi-site and locale

Pass `siteId` and `locale` when the form should render for a specific Craft site or language:

```tsx
<FormieForm
    transport="graphql"
    endpoint="https://your-craft-site.test/api"
    formHandle="contactForm"
    siteId={2}
    locale="fr-CA"
/>
```

Formie merges [site translation overrides](/forms/multi-site#content-translation) automatically. You do not need separate forms per language for label changes.

## Step 6 — Static caching

If the React page is served from a static cache (Blitz, Craft Cloud, or a CDN), enable token refresh so CSRF and request tokens stay fresh:

```tsx
<FormieForm
    transport="rest"
    endpoint="https://your-craft-site.test"
    formHandle="contactForm"
    staticCache
    refreshTokens
/>
```

Also enable `staticCacheRefreshOnLoad` in `config/formie.php` when Blitz is not auto-detected. See [Cached forms in production](/guides/frontend-headless/cached-forms-in-production) for Craft Cloud async CSRF and edge-cache details.

## Step 7 — Styling

Three common approaches, from lightest to heaviest:

1. **CSS variables** — override Formie tokens on a wrapper:

```css
.contact-form {
  --formie-color-primary: #0f766e;
  --formie-button-primary-background: var(--formie-color-primary);
}
```

2. **Theme config** — pass overrides at mount time:

```tsx
<FormieForm
    formHandle="contactForm"
    transport="rest"
    endpoint="https://your-craft-site.test"
    themeConfig={{
        form: { attributes: { class: 'contact-form' } },
    }}
/>
```

3. **Client-rendered components** — full control via `<FormieClientForm />` field and slot components.

## Step 8 — Analytics

If you configured client events in the form builder, resolved events are returned in the submit response. Push them to your analytics layer in `onSubmitSuccess`:

```tsx
<FormieForm
    formHandle="contactForm"
    transport="rest"
    endpoint="https://your-craft-site.test"
    onSubmitSuccess={(result) => {
        result.clientEvents?.forEach(({ payload }) => {
            window.dataLayer?.push(payload);
        });
    }}
/>
```

See [Client events — GTM, GA4, and Meta](/guides/frontend-headless/client-events-gtm-ga4-and-meta) for builder setup and GTM wiring.

## Starter projects

The [React starter](https://formie-starters.verbb.io/react) and [Next.js starter](https://formie-starters.verbb.io/next) exercise all four combinations: HTML/component rendering × REST/GraphQL transport. Clone one of those repos when you want a working reference app rather than wiring from scratch.

## Troubleshooting

**Form does not load**

- Confirm the form handle, site availability, and that the public schema includes Formie queries.
- Check the browser network tab for 403/CORS errors on bootstrap requests.

**CSRF validation failed**

- Ensure the client transport sends the token from `session.tokens.csrf`.
- After static caching, confirm `refreshTokens` ran before submit.

**Validation errors on submit**

- GraphQL returns field errors in `extensions.errors`. REST returns them in the submit response body. Both map to field handles configured in the form builder.

## Related

- [Frontend Assets](/frontend/frontend-assets)
- [GraphQL submission flow end-to-end](/guides/frontend-headless/graphql-submission-flow-end-to-end)
- [Cached forms in production](/guides/frontend-headless/cached-forms-in-production)
- [Client events — GTM, GA4, and Meta](/guides/frontend-headless/client-events-gtm-ga4-and-meta)
- [React package docs](https://docs.verbb.io/formie/react/)
