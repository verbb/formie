# How to conditionally redirect users based on their input

Sometimes the thank-you destination should depend on what the user submitted — send opted-in users to one URL and everyone else to another, route by department, or branch on a quiz score. Formie supports this natively with **Redirect Rules**; the patterns below cover cases where you need more control.

## Prerequisites

- A form with submit action set to **URL** or **Entry**
- Familiarity with [Conditions](/forms/conditions) and [Reference tokens](/developers/reference-tokens)

## Use Redirect Rules (recommended)

Formie adds **Enable Redirect Rules** in form settings. Each rule has conditions and a redirect target. Rules are evaluated in order; the first match wins.

1. Open your form in the form builder
2. Under submit settings, enable **Enable Redirect Rules**
3. Add rules with conditions (for example, "Agree field is checked") and set each rule's redirect URL or entry

This works for both page-reload and Ajax forms, stays in the control panel, and does not require custom code. Prefer this approach whenever it covers your logic.

## Intermediary template

When redirect rules are not enough — for example, you need Twig logic that conditions cannot express — use an intermediary template.

Set the form redirect to a path that includes the submission ID:

```twig
{% do form.setSettings({
    redirectUrl: '/my-success-template?submissionId={submission:id}',
}) %}
```

Create a Craft route in `config/routes.php`:

```php
return [
    'my-success-template' => ['template' => '_forms/redirect'],
];
```

In `_forms/redirect.html`, fetch the submission and branch:

```twig
{% set submissionId = craft.app.request.getParam('submissionId') %}
{% set submission = craft.formie.submissions.id(submissionId).one() %}

{% if not submission %}
    {% exit 404 %}
{% endif %}

{% if submission.getFieldValue('myFieldToCheck') == 'some-value' %}
    {% redirect 'https://example.com/thanks' %}
{% else %}
    {% redirect 'https://example.com/sorry' %}
{% endif %}
```

This approach requires a full page reload (not Ajax-only in-place completion) because the redirect target is a server-rendered template.

## JavaScript with a hidden field

For Ajax forms, you can drive the redirect URL from a hidden field whose value JavaScript updates when another field changes. Formie resolves reference tokens in the redirect URL at submit time.

Create two fields:

- An Agree field (handle `verified`)
- A Hidden field (handle `redirectParam`)

Set the form's redirect URL to `{field:redirectParam}` using the variable picker (insert the hidden field's reference token).

Add JavaScript where the form is rendered:

```javascript
{% js %}
const form = document.querySelector('[data-formie-form]');
const verifiedField = form?.querySelector('[data-formie-field-handle="verified"]');
const verifiedInput = verifiedField?.querySelector('input[type="checkbox"]');
const redirectParamInput = form?.querySelector('[data-formie-field-handle="redirectParam"] input');

const updateRedirectParam = (checked) => {
    redirectParamInput.value = checked ? '/thanks' : '/sorry';
};

verifiedField?.addEventListener('onAfterFormieEvaluateConditions', (event) => {
    if (event.target.conditionallyHidden) {
        updateRedirectParam(false);
    } else {
        verifiedInput?.dispatchEvent(new Event('change', { bubbles: true }));
    }
});

verifiedInput?.addEventListener('change', (event) => {
    updateRedirectParam(event.target.checked);
});
{% endjs %}
```

Notes:

- Use `[data-formie-form]` and `[data-formie-field-handle="..."]` — Formie's front-end selectors
- Listen for `onAfterFormieEvaluateConditions` so hidden conditional fields evaluate as unchecked
- The redirect URL is hashed in the form markup; you cannot change it directly — the hidden field + reference token is the supported workaround

## PHP module event

For Ajax and page-reload forms, hook into the submission workflow and override the redirect URL in PHP:

```php
use verbb\formie\events\SubmissionWorkflowStageEvent;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_AFTER_STAGE, function (SubmissionWorkflowStageEvent $event) {
    if ($event->stage !== 'finalize') {
        return;
    }

    $request = $event->request;
    $submission = $request->submission;
    $form = $request->form;

    if ($event->context->nextPage !== null) {
        return;
    }

    if ($submission->getFieldValue('myFieldToCheck') === 'some-value') {
        $form->setRedirectUrl('/some-url');
    }
});
```

This runs after the submission workflow completes on the final page. Logic stays server-side and works with Ajax responses.

## Related

- [Conditions](/forms/conditions)
- [Submission Workflow](/developers/submission-workflow)
- [Reference tokens](/developers/reference-tokens)
- [Overriding Settings](/templates/overriding-settings)
