# Tracking and Analytics

::: tip
For GTM, GA4, and Meta setup walkthroughs, see [Client events — GTM, GA4, and Meta](/guides/frontend-headless/client-events-gtm-ga4-and-meta). To register reusable presets for authors, see [Custom client event templates](/guides/frontend-headless/custom-client-event-templates).
:::

Form submissions are often important analytics events. Google Tag Manager is the common example, but the same approach can be used for other tools that read from `window.dataLayer`, or for your own front-end code that listens for Formie submission events.

The main thing to decide is when the event should be recorded. On a single-page form, that is usually after the form is completed. On a multi-page form, you might want an event on every page step, or only when the final submit succeeds.

## Form Builder Events

You can configure analytics events per page from the form builder. Open the form, select the page you want to track, then go to the **Tracking** tab. Enable **Client Events** and add one or more events.

Each event has:

- **Event Name** — the analytics event identifier (for GTM, this is usually pushed as the `event` property on the payload).
- **Payload properties** — key/value pairs pushed to `dataLayer` and the `formie:client-event` DOM event.

For payload **values**, use the [variable picker](/developers/reference-tokens). Formie stores reference tokens such as `{field:a1b2c3}` for fields and `{form:handle}` for form variables. Do not type a field handle directly in braces — field tokens use each field's stable reference, and the picker inserts the correct token for the current form.

Values are resolved server-side after a successful page submit, so they can include submission metadata and complex field values.

A simple page-level event might look like:

Event Name | Property | Value
--- | --- | ---
`formPageSubmission` | `formHandle` | `{form:handle}` *(from the variable picker)*
`formPageSubmission` | `email` | `{field:a1b2c3}` *(field reference token from the picker)*

That represents the same payload you would normally send to `dataLayer.push()`:

```js
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({
    event: 'formPageSubmission',
    formHandle: 'contactForm',
    email: 'jane@example.com',
});
```

You can configure multiple events per page. Each configured event results in a separate `dataLayer.push()` after a successful page submit.

Use a different `event` value if your analytics setup expects one. The payload is a plain object suitable for `dataLayer.push()` and for listeners on the `formie:client-event` DOM event.

### Event templates

The builder includes predefined templates for common analytics setups such as GTM page submits, GA4 `generate_lead`, and Meta `Lead` events. Use **Add event** on the Tracking tab, or pick from **Suggested for this page** when a template matches the current page context.

Templates that need form data will prompt you to map fields first. Formie inserts the correct field reference tokens for you. After insertion, the event is stored like any other client event and can be edited freely.

Plugins and modules can register additional templates with [`ClientEventTemplates::EVENT_REGISTER_CLIENT_EVENT_TEMPLATES`](/developers/custom-client-event-templates).

Each event also supports optional **conditions**, so you can limit when a configured event is pushed.

### Form defaults

Use **Behaviour → Client Event Defaults** to define events once for the whole form. Pages that enable client events but do not define their own events will inherit these defaults. Use **Apply defaults to all pages** to copy the default set onto every page’s Tracking settings.

## Ajax Submit Response

For Ajax and headless submissions, resolved client events are returned in the submit response as `clientEvents`:

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

## Template Control

If you need more control over the event, add your own JavaScript in the template where the form is rendered. This is useful when the payload needs extra template data, when the event should only fire for a very specific result, or when your analytics setup does not use `dataLayer`.

For Ajax forms, listen to Formie's submit result event and only push your analytics event after a successful final submission:

```twig
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}

{{ craft.formie.renderForm(form) }}

{% js %}
    const formEl = document.querySelector('#{{ form.getRenderId() }}');

    formEl?.addEventListener('formie:submit:result', (event) => {
        const result = event.detail;

        if (!result.ok || result.nextPage) {
            return;
        }

        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            event: 'formSubmission',
            formHandle: '{{ form.handle }}',
            formTitle: '{{ form.title|e('js') }}',
        });
    });
{% endjs %}
```

The `result.nextPage` check matters for multi-page Ajax forms. Formie saves the current page as the user moves through the form, so this check avoids recording a final conversion event on an intermediate page step.

For page reload forms, you can check Formie's submitted flash value after the form has completed:

```twig
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}
{% set submitted = craft.formie.plugin.service.getFlash(form.getFlashNamespace(), 'submitted') %}

{{ craft.formie.renderForm(form) }}

{% if submitted %}
    {% js %}
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            event: 'formSubmission',
            formHandle: '{{ form.handle }}',
            formTitle: '{{ form.title|e('js') }}',
        });
    {% endjs %}
{% endif %}
```

If the form redirects to a thank-you page, put the analytics code on the redirected page instead. In that case, the thank-you page itself is the completion signal.

You can also listen for builder-configured events directly:

```js
formEl?.addEventListener('formie:client-event', (event) => {
    console.log('Client event dispatched:', event.detail);
});
```
