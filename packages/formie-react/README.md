# @verbb/formie-react

React bindings for Formie's front-end stack:

- `server-rendered` wraps `@verbb/formie-browser` for Formie-owned rendering.
- `client-rendered` composes `@verbb/formie-core` for app-owned rendering over REST or GraphQL.

## Install

```bash
npm install @verbb/formie-browser @verbb/formie-core @verbb/formie-react react react-dom
```

## Usage

```tsx
import { FormieForm } from '@verbb/formie-react';
import '@verbb/formie-browser/css/formie.css';

export function ContactForm() {
    return (
        <FormieForm
            transport="rest"
            endpoint="https://site.test"
            formHandle="contactForm"
            theme="formie"
        />
    );
}
```

Server-rendered GraphQL can also use a payload your app fetched itself:

```tsx
import { FormieForm, type FormEndpointPayload } from '@verbb/formie-react';

export function ContactForm({ payload }: { payload: FormEndpointPayload }) {
    return <FormieForm source={{ payload }} />;
}
```

Client-rendered forms can read and submit through the canonical front-end contract:

```tsx
import { FormieClientForm } from '@verbb/formie-react';

export function ContactForm() {
    return (
        <FormieClientForm
            transport="graphql"
            endpoint="https://site.test/api"
            formHandle="contactForm"
            components={{
                Field({ field, children, errors }) {
                    return (
                        <div>
                            <label>{field.label}</label>
                            {children}
                            {errors.map((error) => <div key={error}>{error}</div>)}
                        </div>
                    );
                },
            }}
        />
    );
}
```

## Public Surface

Use only the root package entry:

- `@verbb/formie-react`

CSS continues to come from `@verbb/formie-browser`.

## Recommended Starters

- `formie-starters-repo/react` is the primary review surface for the public React API.
- `formie-starters-repo/next` is a lighter Next.js companion that exercises the same four HTML/component and REST/GraphQL transport stories.
