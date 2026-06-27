# Client events — GTM, GA4, and Meta

Form submissions are conversion events. Formie lets you configure analytics payloads in the form builder — no custom JavaScript required for the common GTM, GA4, and Meta setups. This walkthrough wires a contact form through Google Tag Manager, with notes for headless apps and multi-page forms.

## Prerequisites

- A form with at least one page (single-page is fine for a first pass)
- Google Tag Manager installed on the site (`window.dataLayer` available)
- [Tracking and Analytics](/frontend/tracking-and-analytics)

## How client events work

1. You configure events per page in the form builder **Tracking** tab.
2. After a **successful page submit**, Formie resolves payload values server-side (field values, form metadata, and so on).
3. Formie pushes each event to `window.dataLayer` and dispatches a `formie:client-event` DOM event.

For Ajax and headless submissions, the same resolved events appear in the submit response as `clientEvents`.

Each event has:

- **Event Name** — the analytics identifier (GTM uses this as the `event` property).
- **Payload properties** — key/value pairs pushed to `dataLayer`.

Use the [variable picker](/developers/reference-tokens) for values. Field tokens use stable references like `{field:a1b2c3}`, not raw handles.

## Step 1 — Enable client events on the form

1. Open your contact form in the form builder.
2. Select the page you want to track (for a single-page form, that is the only page).
3. Open the **Tracking** tab.
4. Enable **Client Events**.

## Step 2 — Add a GTM page submit event

Click **Add event** and choose the **GTM page submit** template (or add a blank event and configure manually).

Example payload:

| Property | Value (from variable picker) |
| --- | --- |
| `formHandle` | `{form:handle}` |
| `formTitle` | `{form:title}` |
| `email` | `{field:…}` *(pick your Email field)* |

That produces a `dataLayer.push()` equivalent to:

```js
window.dataLayer.push({
    event: 'formPageSubmission',
    formHandle: 'contactForm',
    formTitle: 'Contact us',
    email: 'jane@example.com',
});
```

### Form-wide defaults

For events that should fire on every page, define them once under **Behaviour → Client Event Defaults**, then use **Apply defaults to all pages**. Pages with client events enabled but no page-specific events inherit the defaults.

## Step 3 — Configure GTM

In Google Tag Manager:

1. Create a **Custom Event** trigger named `Form Page Submission` with event name `formPageSubmission` (or whatever event name you configured).
2. Create **Data Layer Variables** for each payload key (`formHandle`, `email`, and so on).
3. Create a **Google Analytics: GA4 Event** tag (or your preferred tag type) bound to that trigger.
4. Map Data Layer variables to tag parameters.

Publish the container and test with GTM Preview mode.

## Step 4 — GA4 generate_lead

Formie includes a **GA4 generate_lead** template. Add it from **Add event** on the final page of a multi-page form (or the only page of a single-page form).

The template prompts you to map:

- Email field
- Phone field (optional)
- Value/currency fields (optional, for lead value)

After insertion, the event is stored like any other client event and can be edited freely. In GTM, create a trigger for the configured event name (often `generate_lead`) and a GA4 tag with `event_name` set to `generate_lead` and user properties mapped from Data Layer variables.

## Step 5 — Meta Lead event

Use the **Meta Lead** template for Facebook/Meta Pixel setups. Map the email field when prompted.

In GTM (or your Meta pixel integration), bind a tag to the Formie event name and pass hashed user data according to Meta's current requirements. Formie resolves the email server-side before the event fires — the front end receives the resolved value in the payload, not a template token.

## Multi-page forms

On multi-page forms, client events fire after each **successful page submit**, not only on the final page.

| Goal | Configure on |
| --- | --- |
| Track every step | Each page's Tracking tab |
| Track only final conversion | Last page only; use `pageContexts` when registering custom templates |
| Avoid false conversions in custom JS | Check `!result.nextPage` before pushing a final event |

If you add custom JavaScript instead of builder events, the `result.nextPage` check matters for Ajax forms:

```js
formEl?.addEventListener('formie:submit:result', (event) => {
    const result = event.detail;

    if (!result.ok || result.nextPage) {
        return;
    }

    window.dataLayer.push({ event: 'formSubmission', formHandle: 'contactForm' });
});
```

## Conditions

Each client event supports optional **conditions** — limit when an event fires based on field values, user state, or other rules. Use conditions when one page should push different events depending on a dropdown answer, for example.

## Headless and React/Vue apps

Builder-configured events are resolved server-side and returned in the submit response:

```json
{
  "success": true,
  "clientEvents": [
    {
      "event": "formPageSubmission",
      "payload": {
        "event": "formPageSubmission",
        "formHandle": "contactForm",
        "email": "jane@example.com"
      }
    }
  ]
}
```

Push them in your framework callback:

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

For server-rendered Twig forms, Formie pushes to `dataLayer` automatically — you do not need the callback unless you want additional logic.

## Listen for builder events in custom JS

```js
formEl?.addEventListener('formie:client-event', (event) => {
    console.log('Client event dispatched:', event.detail);
});
```

Useful when GTM is not on the page, or when you need to fan out to multiple analytics tools.

## Redirect and thank-you pages

If the form redirects to a thank-you page after success, put analytics code on the thank-you page instead — the redirect itself is the completion signal. Builder client events still fire before redirect on Ajax forms; for page-reload submits, use the flash-based pattern in [Tracking and Analytics](/frontend/tracking-and-analytics#template-control).

## Related

- [Tracking and Analytics](/frontend/tracking-and-analytics)
- [Custom client event templates](/guides/frontend-headless/custom-client-event-templates)
- [Building a headless contact form with Formie React](/guides/frontend-headless/building-a-headless-contact-form-with-formie-react)
