# Frontend & Headless

npm packages, React/Vue/Web Components, GraphQL, caching, and client events.


##### [Building a headless contact form with Formie React](/guides/frontend-headless/building-a-headless-contact-form-with-formie-react)

This walkthrough takes you from a Formie contact form in Craft to a working React front end that loads, renders, and submits the form over REST or GraphQL — without Twig templates on the page.

##### [Cached forms in production](/guides/frontend-headless/cached-forms-in-production)

Statically cached pages can serve form HTML with stale CSRF tokens, request tokens, and render IDs — which produces random-looking submit failures. Formie refreshes those values automatically when static-cache support is enabled. This guide covers production setups for Twig-rendered forms, Blitz, Craft Cloud, and headless React/Vue apps.

##### [Client events — GTM, GA4, and Meta](/guides/frontend-headless/client-events-gtm-ga4-and-meta)

Form submissions are conversion events. Formie lets you configure analytics payloads in the form builder — no custom JavaScript required for the common GTM, GA4, and Meta setups. This walkthrough wires a contact form through Google Tag Manager, with notes for headless apps and multi-page forms.

##### [Custom client event templates](/guides/frontend-headless/custom-client-event-templates)

Register analytics presets for the form builder **Tracking** tab — author workflow and a SaaS example. Template properties, payload contract, and troubleshooting live in [Custom Client Event Templates](/developers/custom-client-event-templates).

##### [GraphQL submission flow end-to-end](/guides/frontend-headless/graphql-submission-flow-end-to-end)

This walkthrough follows a contact form from GraphQL bootstrap through page navigation, token refresh, and final submission — the same flow `@verbb/formie-react` and `@verbb/formie-core` use internally. Use it when building a custom GraphQL client or debugging headless submit failures.

##### [Vue and Web Components starter walkthrough](/guides/frontend-headless/vue-and-web-components-starter-walkthrough)

Formie ships first-class packages for Vue and Web Components alongside React. This walkthrough covers the same four integration stories — server-rendered HTML vs client-rendered UI, REST vs GraphQL — using the official starters as the reference implementation.
