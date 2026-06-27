# Using submission workflow events

Most submission extensions do not need a custom task or stage. Formie fires **workflow events** at each stage and task boundary — enough for validation tweaks, audit logging, blocking dispatch on specific forms, and reacting after save or screening.

This walkthrough wires those listeners from a Craft module with four common patterns. When you outgrow events — ordered steps inside a stage, a new pipeline phase, or stable task names in logs — see the [custom task](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch) and [custom stage](/guides/submissions-workflows/adding-a-custom-workflow-stage-from-scratch) guides.

Read [Submission workflow and stages explained](/guides/submissions-workflows/submission-workflow-and-stages-explained) first if the pipeline is new to you. For every event payload, see [Submission Events](/developers/events/submission-events).

## When events are enough

| Extension point | Use when |
| --- | --- |
| `Submission::EVENT_BEFORE_SAVE` / `EVENT_AFTER_SAVE` | The element save itself — including control panel edits, imports, and API updates — not the front-end submit request lifecycle. |
| **`beforeStage` / `afterStage`** | Logic around a whole phase (`validate`, `screen`, `save`, `dispatch`, …). |
| **`beforeTask` / `afterTask`** | Logic beside one built-in step — after validation, after persistence, before integrations. |
| Custom workflow task | You need a **named, ordered** step inside a stage, or multiple extensions sharing one anchor. |
| Custom workflow stage | You need a **new phase** in the pipeline with its own stage name. |

Workflow events receive a `SubmissionRequest` and `WorkflowContext`. That gives you the form, submission, process mode, in-progress response, and shared `taskState` — without implementing `TaskInterface`.

Events cannot skip a single task in the middle of a stage and leave the rest running. Setting `$event->isValid = false` on `beforeTask` halts the **entire stage**. When you need one step inserted or skipped without stopping what comes after, use a custom task.

## Create your module

You need a [Craft module](https://craftcms.com/docs/5.x/extend/module-guide.html). This guide keeps everything in one bootstrap file — no extra classes required.

Set the namespace to `modules\formieworkflow` and the module ID to `formie-workflow`:

```treeview
my-project/
├── modules/
│   └── formieworkflow/
│       └── src/
│           └── FormieWorkflow.php
└── ...
```

Register the module in project config. The examples below all live in `FormieWorkflow.php`:

```php [modules/formieworkflow/src/FormieWorkflow.php]
<?php
namespace modules\formieworkflow;

use verbb\formie\services\SubmissionWorkflow;
use yii\base\Module;

class FormieWorkflow extends Module
{
    public function init(): void
    {
        parent::init();

        $this->_registerSubmissionWorkflowEvents();
    }

    private function _registerSubmissionWorkflowEvents(): void
    {
        // Listeners from the sections below.
    }
}
```

## Guard on process mode

Workflow events fire for every request that walks the pipeline — full submits, save-and-continue drafts, edit-existing updates, and payment replays. Most front-end-only logic should return early unless the mode is a full submit:

```php
if ($event->request->processMode !== SubmissionWorkflow::PROCESS_MODE_SUBMIT) {
    return;
}
```

Built-in tasks inside inactive stages are skipped automatically. **Stage-level** listeners still run when the pipeline reaches that stage, so always check `processMode` when the behaviour should not apply to drafts or replays.

Task-level listeners only fire when that built-in task runs for the current mode — so `afterTask` on `dispatch.triggerIntegrations` does not run on save-and-continue, even without an explicit guard.

## Example: reject blocked email domains after validation

Use `afterTask` on `validate.validateSubmission` when Formie has already run field rules and you want to add project-specific checks. Errors on the submission are picked up by `authorize.haltOnSubmissionErrors` later in the pipeline.

```php
use verbb\formie\enums\workflow\Task;
use verbb\formie\events\SubmissionWorkflowTaskEvent;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_AFTER_TASK, function(SubmissionWorkflowTaskEvent $event) {
    if ($event->task !== Task::VALIDATE_SUBMISSION->value) {
        return;
    }

    if ($event->request->processMode !== SubmissionWorkflow::PROCESS_MODE_SUBMIT) {
        return;
    }

    $email = (string)$event->request->submission->getFieldValue('emailAddress');
    $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));
    $blocked = ['mailinator.com', 'tempmail.com'];

    if (in_array($domain, $blocked, true)) {
        $event->request->submission->addError('emailAddress', 'Please use a work email address.');
    }
});
```

Use the [default tasks table](/developers/submission-workflow#default-tasks) for anchor names. Task names follow the `stage.handle` pattern — for example `dispatch.sendNotifications`, not `notifications`.

## Example: sync to an external system after save

Use `afterTask` on `save.persistSubmissionWorkflow` when the submission is stored and you want side effects before notifications or integrations. The submission has an ID; payment processing may still run in later save tasks.

```php
use Craft;
use verbb\formie\enums\workflow\Task;
use verbb\formie\events\SubmissionWorkflowTaskEvent;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_AFTER_TASK, function(SubmissionWorkflowTaskEvent $event) {
    if ($event->task !== Task::SAVE_PERSIST_SUBMISSION_WORKFLOW->value) {
        return;
    }

    if ($event->request->processMode !== SubmissionWorkflow::PROCESS_MODE_SUBMIT) {
        return;
    }

    if (!$event->result?->success) {
        return;
    }

    $submission = $event->request->submission;

    // POST to an internal API, write an audit row, etc.
    Craft::info('Submission saved: ' . $submission->id, __METHOD__);
});
```

Check `$event->result` on `afterTask` and `afterStage` when you only want to react to successful work.

## Example: skip dispatch for internal test forms

Use `beforeStage` on `dispatch` when an entire phase should not run. Set `$event->isValid = false` to halt the stage before any dispatch task executes — no notifications, integrations, or spam admin emails.

```php
use verbb\formie\events\SubmissionWorkflowStageEvent;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_BEFORE_STAGE, function(SubmissionWorkflowStageEvent $event) {
    if ($event->stage !== 'dispatch') {
        return;
    }

    if ($event->request->form->handle === 'internalSmokeTest') {
        $event->isValid = false;
    }
});
```

The workflow stops at dispatch; finalize still runs so the visitor gets a coherent response. Use this sparingly — disabling notifications and integrations in form settings is usually clearer for editors.

## Example: react after spam screening

Use `afterStage` on `screen` when you care about the outcome of the whole screening phase rather than one task. `$event->result` describes whether the stage succeeded; the submission may already be flagged as spam.

```php
use Craft;
use verbb\formie\events\SubmissionWorkflowStageEvent;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_AFTER_STAGE, function(SubmissionWorkflowStageEvent $event) {
    if ($event->stage !== 'screen') {
        return;
    }

    if ($event->request->processMode !== SubmissionWorkflow::PROCESS_MODE_SUBMIT) {
        return;
    }

    $submission = $event->request->submission;

    if ($submission->isSpam) {
        Craft::warning('Submission flagged as spam: ' . ($submission->spamReason ?? 'unknown'), __METHOD__);
    }
});
```

## Vetoing a task with `beforeTask`

`beforeTask` and `beforeStage` extend Craft's `CancelableEvent`. Setting `$event->isValid = false` halts the current stage immediately — remaining tasks in that stage do not run.

```php
use verbb\formie\enums\workflow\Task;
use verbb\formie\events\SubmissionWorkflowTaskEvent;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_BEFORE_TASK, function(SubmissionWorkflowTaskEvent $event) {
    if ($event->task !== Task::DISPATCH_TRIGGER_INTEGRATIONS->value) {
        return;
    }

    if ($event->request->submission->getFieldValue('skipIntegrations')) {
        $event->isValid = false;
    }
});
```

This stops **all** remaining dispatch work — including `dispatch.markDispatchFinalized`. Prefer form settings, integration conditions, or a [custom task](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch) inserted before integrations when you only want to skip one step.

## Workflow events vs element events

`Submission::EVENT_AFTER_SAVE` fires whenever the submission **element** is written — front-end submit, save-and-continue, control panel edit, queue jobs, imports.

Workflow events fire during the **submission request** and respect stage and task boundaries. Use them when behaviour should only run on a full front-end submit, only after screening, or only before integrations.

If both could work, prefer the narrowest hook. Element events are broader; workflow events are more precise for submit-time behaviour.

## When to move to a custom task or stage

| Symptom | Better approach |
| --- | --- |
| Several modules need ordering relative to the same anchor | [Custom task](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch) |
| Listeners are getting long and hard to test | Custom task class |
| Skip one dispatch step but continue the rest | Custom task — not `beforeTask` veto |
| New phase between built-in stages | [Custom stage](/guides/submissions-workflows/adding-a-custom-workflow-stage-from-scratch) |
| React to any save regardless of source | `Submission::EVENT_AFTER_SAVE` |

## Finishing up

1. Enable Craft dev mode and submit a form — watch `storage/logs/` for `Starting workflow stage` and `Starting workflow task` lines to confirm where your listener runs.
2. Test save-and-continue and a full final submit — confirm guards behave as expected.
3. If a listener never fires, check the task name against the [default tasks table](/developers/submission-workflow#default-tasks) and confirm the task runs in your workflow mode.

Keep listeners fast. Queue slow API calls from `afterTask` or `afterStage` rather than blocking the visitor.

## Related

- [Submission workflow and stages explained](/guides/submissions-workflows/submission-workflow-and-stages-explained)
- [Adding a custom workflow task from scratch](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch)
- [Adding a custom workflow stage from scratch](/guides/submissions-workflows/adding-a-custom-workflow-stage-from-scratch)
- [Submission Workflow](/developers/submission-workflow)
- [Submission Events](/developers/events/submission-events)
