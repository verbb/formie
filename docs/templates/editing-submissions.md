# Editing Submissions

::: tip
For logged-in and signed-URL patterns, edit-existing workflow behaviour, and multi-page notes, see [Editing submissions on the front end](/guides/submissions-workflows/editing-submissions-on-the-front-end).
:::

Formie can render a saved submission back into the form so someone can edit it from the front end.

That is useful for account areas, review flows, or any project where a submission may need to be updated after it was first created.

## The Basic Pattern

1. fetch the submission
2. make sure the current user is allowed to edit it
3. set that submission on the form
4. render the form again

This example assumes visitors signed in before submitting `contactForm`, and the form collected the current user (`collectUser`). Put it in the account-area template that receives `submissionUid` in its query string. Exclude the route from full-page caching.

```twig
{% requireLogin %}
{% header "Cache-Control: private, no-store" %}
{% set submissionUid = craft.app.request.getQueryParam('submissionUid') %}
{% if not submissionUid or submissionUid is iterable or not (submissionUid matches '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i') %}
    {% exit 404 %}
{% endif %}

{% set submission = craft.formie.submissions()
    .form('contactForm')
    .siteId(currentSite.id)
    .uid(submissionUid)
    .userId(currentUser.id)
    .one() %}

{% if not submission %}
    {% exit 404 %}
{% endif %}

{% do submission.form.setSubmission(submission) %}
{{ craft.formie.renderForm(submission.form) }}
```

Verify that the owner can edit and that a different signed-in account receives 404 for the same URL.

## Security

Calling `setSubmission()` is an access decision. Only set a submission on a front-end form after your template, route, controller, or module has checked that the current visitor should be allowed to edit that submission.

When an edit form is rendered, Formie includes an edit capability token in the form. The token is required when the form is posted back, so a visitor cannot edit a submission by changing only the submitted `submissionId`.

The token works as bearer access for that rendered edit form. This means unauthenticated edit flows are supported, but the page or link that renders the edit form should be treated as private access to that submission.
