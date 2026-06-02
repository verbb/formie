# Tracking and Analytics

Form submissions are often important analytics events. Google Tag Manager is the common example, but the same approach can be used for other tools that read from `window.dataLayer`, or for your own front-end code that listens for Formie submission events.

The main thing to decide is when the event should be recorded. On a single-page form, that is usually after the form is completed. On a multi-page form, you might want an event on every page step, or only when the final submit succeeds.

## Form Builder Events

You can configure event data on a page button from the form builder. Open the form, click the submit or next button for the page you want to track, then go to the **Advanced** tab. Enable **Client Events** and the **Client Event Data** table will appear.

Add the option/value pairs you want to send. A simple page-level event might use:

Option | Value
--- | ---
`event` | `formPageSubmission`
`formId` | `contactForm`
`pageId` | `3074`
`pageIndex` | `0`

That represents the same payload you would normally send to `dataLayer.push()`:

```js
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({
    event: 'formPageSubmission',
    formId: 'contactForm',
    pageId: '3074',
    pageIndex: '0',
});
```

Use a different `event` value if your analytics setup expects one. The payload is a plain object suitable for `dataLayer.push()` and for listeners on the `formie:client-event` DOM event.

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
