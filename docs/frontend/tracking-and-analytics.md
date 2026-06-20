# Tracking and Analytics

Form submissions are often important analytics events. Google Tag Manager is the common example, but the same approach can be used for other tools that read from `window.dataLayer`, or for your own front-end code that listens for Formie submission events.

The main thing to decide is when the event should be recorded. On a single-page form, that is usually after the form is completed. On a multi-page form, you might want an event on every page step, or only when the final submit succeeds.

## Form Builder Events

You can configure analytics events per page from the form builder. Open the form, select the page you want to track, then go to the **Tracking** tab. Enable **Client Events** and add one or more events.

Each event has:

- **Event Name** — the analytics event identifier (for GTM, this is usually pushed as the `event` property on the payload).
- **Payload properties** — key/value pairs pushed to `dataLayer` and the `formie:client-event` DOM event.

For payload **values**, use the variable picker in the builder. Formie stores reference tokens such as `{field:a1b2c3}` for fields and `{form:handle}` for form variables. Do not type a field handle directly in braces — field tokens use each field's stable reference, and the picker inserts the correct token for the current form.

Values are resolved server-side after a successful page submit, so they can include submission metadata and complex field values.

A simple page-level event might look like:

Event Name | Property | Value
--- | --- | ---
`formPageSubmission` | `formHandle` | `{form:handle}` *(from the variable picker)*
`formPageSubmission` | `email` | `{field:a1b2c3}` *(field reference token from the picker)*

That represents the same payload you would normally send to `dataLayer.push()`:

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
