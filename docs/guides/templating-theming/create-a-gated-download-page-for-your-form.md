# Create a gated download page for your form

A gated download asks users to complete a form before accessing a resource — an ebook, whitepaper, or template. You might combine it with a Payment field for paid downloads. This guide redirects to a protected thank-you page that reveals the asset only after a valid submission.

See also [Build a success page for your form](/guides/templating-theming/build-a-success-page-for-your-form) for the underlying redirect-and-fetch pattern.

## Prerequisites

- A form (the Contact Form stencil works well)
- A Craft [asset](https://craftcms.com/docs/5.x/assets.html) to gate
- [Submission Queries](/getting-elements/submission-queries)

## Render the form with a redirect

Point the form at a download page after submission, passing the submission UID in the query string:

```twig
{# templates/form.html #}

{% set form = craft.formie.forms.handle('contactForm').one() %}

{% do form.setSettings({
    redirectUrl: '/download?submissionUid={submission:uid}',
}) %}

{{ craft.formie.renderForm(form) }}
```

Use `{submission:uid}` (a [reference token](/developers/reference-tokens)) rather than the numeric ID. UIDs are unguessable, which matters when the download page exposes submission data.

### Alternative: redirect straight to the asset

You can redirect directly to an asset URL on submit:

```twig
{% do form.setSettings({
    submitAction: 'url',
    submitActionUrl: craft.assets.id(10839).one().url,
    submitActionTab: 'new-tab',
}) %}
```

This works, but the user is taken to the file immediately with no thank-you message. For most gated downloads, a dedicated landing page feels less abrupt.

## Build the download page

The download template validates the submission, then shows the asset link:

```twig
{# templates/download.html #}

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

<p>Thanks for filling out the form! Here are your resources:</p>

{% set asset = craft.assets.id(10839).one() %}

{{ asset.getLink() }}
```

Exit early with `{% exit 404 %}` when the parameter is missing or invalid. Do not leak whether a UID almost matched.

## Restrict to registered users

To require a Craft account, match the submitter's email against a user record:

```twig
{% set emailAddress = submission.getFieldValueAsString('emailAddress') %}
{% set user = craft.users.email(emailAddress).one() %}

{% if not user %}
    {% exit 404 %}
{% endif %}
```

Adjust the field handle to match your form.

## Require successful payment

For paid downloads, add a Payment field (Stripe is a common choice) and verify payment status before revealing the asset:

```twig
{% set paid = false %}

{% for payment in submission.getPayments() %}
    {% if payment.status == 'success' %}
        {% set paid = true %}
    {% endif %}
{% endfor %}

{% if not paid %}
    {% exit 404 %}
{% endif %}
```

The form's own validation should prevent unpaid submissions, but checking again on the download page is good defence in depth.

## Related

- [Build a success page for your form](/guides/templating-theming/build-a-success-page-for-your-form)
- [Rendering Forms](/templates/rendering-forms)
- [Submission Queries](/getting-elements/submission-queries)
- [Reference tokens](/developers/reference-tokens)
