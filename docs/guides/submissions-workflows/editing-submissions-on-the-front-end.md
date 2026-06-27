# Editing submissions on the front end

Formie can render a saved submission back into the form so someone can update it from the front end — account areas, application review flows, or any case where data may change after first submit.

## Prerequisites

- [Editing Submissions reference](/templates/editing-submissions)
- A route or template that loads the submission securely

## The basic pattern

1. Fetch the submission.
2. Confirm the current visitor may edit it.
3. Set the submission on the form.
4. Render the form.

```twig
{% set submission = craft.formie.submissions.id(craft.app.request.getSegment(3)).one() %}

{% if not submission %}
    {% exit 404 %}
{% endif %}

{# Your access check here — see Security below #}

{% do submission.form.setSubmission(submission) %}

{{ craft.formie.renderForm(submission.form) }}
```

On POST, Formie updates the existing submission instead of creating a new one.

## Security

Calling `setSubmission()` is an **access decision**. Only set a submission after your template, route, controller, or module confirms the visitor may edit it.

When an edit form renders, Formie includes an **edit capability token** in the form. The token is required on POST — a visitor cannot edit a submission by guessing or changing only `submissionId`.

The token is bearer access for that rendered edit form. Unauthenticated edit flows are supported, but treat the page URL as private — anyone with the link can edit until the token expires or the submission is deleted.

### Example: logged-in author only

```twig
{% set submission = craft.formie.submissions()
    .user(currentUser)
    .id(craft.app.request.getParam('id'))
    .one() %}

{% if not submission %}
    {% exit 404 %}
{% endif %}

{% do submission.form.setSubmission(submission) %}
{{ craft.formie.renderForm(submission.form) }}
```

Querying by `user(currentUser)` ensures the submission belongs to the logged-in account.

### Example: signed URL from a module

For email links without login, generate a time-limited signed URL in a controller that verifies the signature before rendering the edit template.

## What happens on save

Edit requests use the `editExisting` workflow mode:

- Field validation runs.
- The submission element is saved.
- Notifications and spam screening are **skipped** (same as CP edits).
- Integrations may re-run depending on **Integrations → Settings → When integrations re-run** and per-integration settings.

If you need notifications on front-end edits, trigger them from `Submission::EVENT_AFTER_SAVE` in a module when `$submission->isEditable()` context applies.

## Multi-page forms

Edit forms respect the submission's current page state. Users can navigate pages and save progress depending on form settings.

Save-and-continue behaviour on edit forms follows the same draft rules as new submissions — see [Save and continue later](/guides/submissions-workflows/save-and-continue-later).

## Pre-populating vs editing

`setSubmission()` is for editing an **existing** record. For pre-populating a **new** submission from defaults or query params, use field default values or `setFieldSettings()` — not `setSubmission()`.

## Styling and success behaviour

After a successful edit, configure redirect URL or message under form **Settings → Submit Action** the same as for new submissions. Use conditions if edit success should differ from first-time submit.

## Related

- [Editing Submissions](/templates/editing-submissions)
- [Relations between submissions and entries](/guides/submissions-workflows/relations-between-submissions-and-entries)
- [Submission workflow and stages explained](/guides/submissions-workflows/submission-workflow-and-stages-explained)
