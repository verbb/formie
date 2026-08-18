# Run PHP on Next vs when the form is finished

Every page POST walks the same submission pipeline. Next is not a different stage from final submit — both use `submit` mode, and both post `submitAction=submit`. Completeness is the outcome of page flow: if there is another reachable page, the submission stays incomplete; if this was the last **visible** page (later pages may be hidden by conditions), it becomes complete.

Use the two public hooks below. You do not need `EVENT_AFTER_TASK` or a custom stage for these cases.

## Which hook?

| You want to… | Hook |
| --- | --- |
| Run code after a successful **Next** (page 1 is accepted, page 2 is about to show) | `SubmissionWorkflow::EVENT_AFTER_PAGE_ADVANCE` |
| Run code when the form is **actually finished** | `Submission::EVENT_AFTER_COMPLETE` |
| React to any element save, including control panel edits and imports | `Submission::EVENT_AFTER_SAVE` |
| Set a status on final submit without PHP | Form settings → **Submission Status Rules** → **Final submit** |

`EVENT_AFTER_COMPLETE` also fires when a control-panel save marks an incomplete submission complete, and when a payment replay finishes the form. It does not fire on later edits of an already complete submission.

Both workflow hooks run after a successful save and **before** notifications and integrations, so a status change is visible to dispatch.

## Example: extra work after Next

```php
use verbb\formie\events\SubmissionPageAdvanceEvent;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_AFTER_PAGE_ADVANCE, function(SubmissionPageAdvanceEvent $event) {
    $submission = $event->submission;
    $fromPage = $event->fromPage;
    $toPage = $event->toPage;

    // Audit, enqueue a job, mutate fields on `$submission`, etc.
});
```

This does not fire on Back, save-and-continue, or single-page forms.

## Example: set a status when the form is finished

Prefer **Submission Status Rules** on the form when the rule is per-form. Use PHP when the logic is shared (for example every form on a `request` template):

```php
use verbb\formie\elements\Submission;
use verbb\formie\events\SubmissionCompleteEvent;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_AFTER_COMPLETE, function(SubmissionCompleteEvent $event) {
    $submission = $event->submission;
    $templateHandle = $submission->getForm()?->getTemplate()?->handle ?? null;

    if ($templateHandle !== 'request') {
        return;
    }

    $submission->setStatus('readyForEvaluation');
    // Formie persists a status change from this listener before dispatch.
});
```

The submission already has an ID. Mutating other attributes still requires `Craft::$app->getElements()->saveElement($submission)` if you need them written immediately; status changes from this listener are saved for you.

## When to use stage and task events

Use `beforeStage` / `afterTask` when you need a slot the two hooks do not name — between persist and payments, after spam screening, skipping one dispatch step. Register a [custom task](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch) when several modules must order relative to the same built-in step. Page vs complete is not a reason to add a stage.

## Related

- [Submission workflow and stages explained](/guides/submissions-workflows/submission-workflow-and-stages-explained)
- [Using submission workflow events](/guides/submissions-workflows/using-submission-workflow-events)
- [Submission Statuses](/submissions/statuses)
- [Submission Events](/developers/events/submission-events)
- [Submission Workflow](/developers/submission-workflow)
