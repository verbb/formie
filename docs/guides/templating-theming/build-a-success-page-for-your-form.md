# Build a success page for your form

After submit, Formie can show a message, hide the form, or redirect elsewhere. A dedicated success page lets you thank the user and show what they entered — useful for confirmations, gated content, or support requests.

You can also show a summary *before* submit using the [Summary field](/fields/summary). This guide covers post-submit display.

## Prerequisites

- A form with submit action set to redirect to a URL
- [Submission Content](/developers/submission-content) basics

## Same-page success (page reload)

This pattern keeps the user on the form URL but swaps the form for a submission summary. It requires **Page Reload** as the submit method because the template must re-render server-side.

Create a form (the Contact Form stencil is fine) and ensure submit method is **Page Reload**.

```twig
{% set formSubmitted = false %}
{% set submissionId = craft.app.request.getParam('submissionId') %}

{% if submissionId %}
    {% set submission = craft.formie.submissions.id(submissionId).one() %}

    {% if submission %}
        {% set formSubmitted = true %}

        <h2>Thanks for your submission</h2>

        {% for field in submission.getFields() %}
            {% set value = submission.getFieldValueAsString(field.handle) %}

            {% if value %}
                <p>
                    <strong>{{ field.name }}</strong><br>
                    {{ value }}
                </p>
            {% endif %}
        {% endfor %}
    {% endif %}
{% endif %}

{% if not formSubmitted %}
    {% set form = craft.formie.forms.handle('contactForm').one() %}

    {% do form.setSettings({
        redirectUrl: craft.app.request.url ~ '?submissionId={submission:id}',
    }) %}

    {{ craft.formie.renderForm(form) }}
{% endif %}
```

After submit, the URL becomes something like `/contact?submissionId=1234`. The template detects the query parameter, fetches the submission, and renders field values with `getFieldValueAsString()` — the right helper for plain-text display.

## Separate thank-you template (works with Ajax)

Splitting templates is often easier to maintain and works with Ajax forms because you redirect to a different page.

**Form template:**

```twig
{# templates/form.html #}

{% set form = craft.formie.forms.handle('contactForm').one() %}

{% do form.setSettings({
    redirectUrl: '/thanks?submissionUid={submission:uid}',
}) %}

{{ craft.formie.renderForm(form) }}
```

**Thank-you template:**

```twig
{# templates/thanks.html #}

{% set submissionUid = craft.app.request.getParam('submissionUid') %}

{% if not submissionUid %}
    {% exit 404 %}
{% endif %}

{% set submission = craft.formie.submissions()
    .uid(submissionUid)
    .one() %}

{% if not submission %}
    {% exit 404 %}
{% endif %}

<h1>Thank you</h1>

{% for field in submission.getFields() %}
    {% set value = submission.getFieldValueAsString(field.handle) %}

    {% if value %}
        <p>
            <strong>{{ field.name }}</strong><br>
            {{ value }}
        </p>
    {% endif %}
{% endfor %}
```

Invalid or missing parameters return 404 immediately — an exit-early flow keeps the template readable.

## Security and privacy

Both patterns pass a submission identifier in the URL. Numeric IDs can be guessed within a range, so prefer `{submission:uid}` over `{submission:id}` when the page shows submission content to unauthenticated users.

UIDs look like `92a47329-67f6-42c6-99e3-eb5b17ed7476` — effectively impossible to enumerate. Swap the query parameter name and fetch with `.uid()` as shown above.

Whether this matters depends on sensitivity. A public contact form summary is lower risk than medical or financial data. When in doubt, use UID and keep the success page minimal.

## Richer output

`getFieldValueAsString()` is the quick option. For formatted HTML, summary blocks, or complex fields, see [The complete guide to rendering submission content](/guides/templating-theming/the-complete-guide-to-rendering-submission-content).

## Related

- [The complete guide to rendering submission content](/guides/templating-theming/the-complete-guide-to-rendering-submission-content)
- [Rendering Forms](/templates/rendering-forms)
- [Submission Content](/developers/submission-content)
- [Reference tokens](/developers/reference-tokens)
