# Editing Submissions

Formie can render a saved submission back into the form so someone can edit it from the front end.

That is useful for account areas, review flows, or any project where a submission may need to be updated after it was first created.

## The basic pattern

1. fetch the submission
2. make sure the current user is allowed to edit it
3. set that submission on the form
4. render the form again

```twig
{% set submission = craft.formie.submissions.id(craft.app.request.getSegment(3)).one() %}

{% if not submission %}
    {% exit 404 %}
{% endif %}

{% do submission.form.setSubmission(submission) %}

{{ craft.formie.renderForm(submission.form) }}
```

## Security

Calling `setSubmission()` is an access decision. Only set a submission on a front-end form after your template, route, controller, or module has checked that the current visitor should be allowed to edit that submission.

When an edit form is rendered, Formie includes an edit capability token in the form. The token is required when the form is posted back, so a visitor cannot edit a submission by changing only the submitted `submissionId`.

The token works as bearer access for that rendered edit form. This means unauthenticated edit flows are supported, but the page or link that renders the edit form should be treated as private access to that submission.
