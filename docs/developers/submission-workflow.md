# Submission Workflow

Formie processes submissions through a staged workflow.

Instead of treating submission handling as one large save step, Formie moves through a series of smaller stages in order. That lets Formie handle multi-page navigation, draft saving, validation, captcha and spam checks, payment handling, persistence, notifications, integrations, and the final browser response in a predictable way.

If you are extending submission handling, the workflow tells you where your code belongs. Instead of guessing where custom logic should run, you can choose the stage or task that owns that responsibility.

## When to use this page

Use the submission workflow when you need to:

- run custom logic at a specific point in submission processing
- add checks before a submission is saved or dispatched
- insert work before or after notifications or integrations
- make logic depend on whether the request is a full submit, a draft save, an edit, or a payment replay

If you only need the full event reference, see [Submission Events](/developers/events/submission-events).

## Choose the right extension point

You do not always need a custom workflow task. Pick the smallest extension point that matches your goal.

| Use this | When it fits best |
| --- | --- |
| Submission element events | You need to react to the element being saved or deleted, regardless of where that save came from. |
| Workflow stage or task events | You need request-level behavior during a specific submission phase such as validation, screening, save, dispatch, or finalize. |
| Custom workflow task | The stage is already correct, but you need to insert one more piece of work into that stage. |
| Custom workflow stage | You need a brand new phase in the pipeline, not just one more task in an existing stage. |

As a rule of thumb, use `Submission::EVENT_AFTER_SAVE` when you care about the element save itself, and use workflow events when you care about the submission request lifecycle.

## Submission stages

1. **`prepare`** sets up the submission request and restores any draft or save-and-continue context before other processing begins.
2. **`normalize`** resolves page flow, back-button behavior, and default values so Formie knows the current submission state.
3. **`validate`** runs the form and field validation rules.
4. **`screen`** runs submission guards, captcha checks, and spam screening before processing continues.
5. **`authorize`** decides whether processing can continue, including payment-state checks and earlier submission errors.
6. **`save`** persists the submission and runs payment-related save logic.
7. **`dispatch`** sends notifications and triggers integrations.
8. **`finalize`** prepares the response, applies progression state, and decides what should happen next on the frontend.

That order matters. Validation needs to happen before a submission can be saved, and integrations do not run until the earlier stages have succeeded.

### Default tasks

Each stage is made up of smaller tasks. These are the default tasks Formie uses for a normal `submit` request.

| Stage | Tasks |
| --- | --- |
| `prepare` | `prepare.applyDraftContext`, `prepare.initializeSubmitRequest` |
| `normalize` | `normalize.handleBackNavigation`, `normalize.resolvePageFlow`, `normalize.ensureSubmissionDefaults` |
| `validate` | `validate.validateSubmission` |
| `screen` | `screen.runSubmissionGuards`, `screen.runCaptchaChecks`, `screen.runSpamChecks` |
| `authorize` | `authorize.haltOnSubmissionErrors`, `authorize.resolvePaymentState` |
| `save` | `save.persistSubmissionWorkflow`, `save.processPayments`, `save.applyCompletionFromPaymentState`, `save.setProcessingSuccess` |
| `dispatch` | `dispatch.guardDispatchEligibility`, `dispatch.sendNotifications`, `dispatch.triggerIntegrations`, `dispatch.sendSpamNotifications`, `dispatch.markDispatchFinalized` |
| `finalize` | `finalize.applySpamBehaviour`, `finalize.applyProgressionState`, `finalize.consumeReplayToken`, `finalize.hydrateResponse` |

The task names are useful when you need to insert your own task before or after a specific built-in task.

## Workflow modes

Not every submission request runs every stage or task.

- `submit` is the normal front-end submission flow. It runs validation, screening, save, dispatch, and finalize work.
- `editExisting` updates an existing submission. It validates and saves, then can re-run integrations configured to run on front-end or control panel edits in **Integrations → Settings → When integrations re-run**. Notifications, captchas, and spam screening are skipped.
- `saveDraft` is used for save-and-continue and back-navigation behavior. It saves submission state without validation, spam screening, notifications, or integrations.
- `paymentReplay` resumes processing after a payment provider callback. It focuses on payment, persistence, dispatch, and response handling.

When you add a task or listen for a task event, choose a stage that runs in the workflow mode you care about. For example, a task added to `dispatch` will not run when a user only saves a draft.

## Practical examples

### Run logic only for full submit requests

Use a workflow event when your code should only run during the full public submit flow and not on drafts or edit-existing requests.

```php
use verbb\formie\events\SubmissionWorkflowStageEvent;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_BEFORE_STAGE, function(SubmissionWorkflowStageEvent $event) {
    if ($event->request->processMode !== SubmissionWorkflow::PROCESS_MODE_SUBMIT) {
        return;
    }

    if ($event->stage !== 'screen') {
        return;
    }

    // Run custom screening logic here.
});
```

### Add work before integrations run

Use a custom task when the stage is already correct, but you need one more step inside it. This example inserts a task into `dispatch` before integrations are triggered.

```php
use verbb\formie\enums\workflow\Task;
use verbb\formie\events\RegisterStageTasksEvent;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_REGISTER_STAGE_TASKS, function(RegisterStageTasksEvent $event) {
    if ($event->stage !== 'dispatch') {
        return;
    }

    $event->insertTaskBefore(Task::DISPATCH_TRIGGER_INTEGRATIONS->value, new PushSubmissionToQueueTask());
});
```

### Run code for any submission save, including edits

If your code should run whenever the submission element is saved, use an element event instead of a dispatch-stage hook. That covers new submissions and edit-existing updates.

```php
use craft\events\ModelEvent;
use verbb\formie\elements\Submission;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_AFTER_SAVE, function(ModelEvent $event) {
    $submission = $event->sender;

    // React to any submission save here.
});
```

## Task results

Stages and tasks report whether work should continue or stop. A task can continue normally, halt successfully, or halt with a failure.

Use `TaskResult::continue()` when your custom task completed and the workflow should keep going.

```php
use verbb\formie\workflow\tasks\TaskResult;

return TaskResult::continue();
```

Use `TaskResult::halt(false)` when the workflow should stop because something failed. This marks the workflow as unsuccessful.

```php
use verbb\formie\workflow\tasks\TaskResult;

return TaskResult::halt(false, [
    'reason' => 'fraudScoreRejected',
]);
```

Use `TaskResult::halt(true)` only when stopping is expected and should still count as successful. For example, a task may decide there is no more work to do for this request.

## Extending the workflow

Formie exposes workflow events for:

- registering stages
- registering tasks inside a stage
- running before a stage
- running after a stage
- running before a task
- running after a task

That lets you extend the workflow without replacing the whole submission pipeline.

### Add a new stage

Use a custom stage when you need a new step in the submission pipeline, such as running a fraud score check after spam screening but before save or dispatch.

```php
use verbb\formie\events\RegisterWorkflowStagesEvent;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_REGISTER_WORKFLOW_STAGES, function(RegisterWorkflowStagesEvent $event) {
    $event->insertStageAfter('screen', new CheckFraudScoreStage());
});
```

### Add a task to an existing stage

Use a custom task when the stage is already correct, but you need one more step inside it.

```php
use verbb\formie\enums\workflow\Task;
use verbb\formie\events\RegisterStageTasksEvent;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_REGISTER_STAGE_TASKS, function(RegisterStageTasksEvent $event) {
    if ($event->stage !== 'dispatch') {
        return;
    }

    $event->insertTaskBefore(Task::DISPATCH_TRIGGER_INTEGRATIONS->value, new PushSubmissionToQueueTask());
});
```

If you only need to observe or adjust behavior around an existing part of the workflow, the before and after stage or task events are often enough.
