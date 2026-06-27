# Adding a custom workflow task from scratch

Formie's submission pipeline is built from **stages**, and each stage runs an ordered list of **tasks**. Most of the time you can hook [stage or task events](/developers/submission-workflow) without writing new classes — but when you need a named step in the right place (before integrations, after spam checks, and so on), you register a custom task and insert it relative to a built-in anchor.

This walkthrough adds one task to the **dispatch** stage: queue an internal review job for high-value orders **before** Formie triggers CRM integrations. The same pattern works in any stage — `screen`, `save`, `finalize`, and the rest.

Read [Submission workflow and stages explained](/guides/submissions-workflows/submission-workflow-and-stages-explained) first if you have not worked with the pipeline yet. For task names, workflow modes, and event reference, see [Submission Workflow](/developers/submission-workflow).

## When a custom task fits

| Approach | Use when |
| --- | --- |
| `Submission::EVENT_AFTER_SAVE` | You care about the element being saved, regardless of how the request arrived. |
| `beforeStage` / `afterStage` | You need request-level logic around an entire phase, without a dedicated task name. |
| `beforeTask` / `afterTask` | A few lines beside an existing built-in task is enough — no new class or ordering contract. |
| **Custom workflow task** | The stage is already correct, but you need a **named, ordered** step inside it. |

Custom tasks are extension tasks: their names are **not** part of Formie's built-in `Task` enum. Formie runs them when the built-in stage is active for the current [workflow mode](/developers/submission-workflow#workflow-modes) — so a task inserted into **dispatch** does not run on save-and-continue drafts, matching built-in dispatch behaviour.

## Create your module

You need a [Craft module](https://craftcms.com/docs/5.x/extend/module-guide.html). All PHP in this guide lives in that module.

Set the namespace to `modules\formieworkflow` and the module ID to `formie-workflow`. Create this structure:

```treeview
my-project/
├── modules/
│   └── formieworkflow/
│       └── src/
│           ├── jobs/
│           │   └── ReviewHighValueSubmissionJob.php
│           ├── tasks/
│           │   └── QueueHighValueReviewTask.php
│           └── FormieWorkflow.php
└── ...
```

Register the module in project config, then wire Formie events in `FormieWorkflow.php`:

```php [modules/formieworkflow/src/FormieWorkflow.php]
<?php
namespace modules\formieworkflow;

use Craft;
use modules\formieworkflow\tasks\QueueHighValueReviewTask;
use verbb\formie\enums\workflow\Task;
use verbb\formie\events\RegisterStageTasksEvent;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;
use yii\base\Module;

class FormieWorkflow extends Module
{
    public function init(): void
    {
        parent::init();

        Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_REGISTER_STAGE_TASKS, function(RegisterStageTasksEvent $event) {
            if ($event->stage !== 'dispatch') {
                return;
            }

            $event->insertTaskBefore(
                Task::DISPATCH_TRIGGER_INTEGRATIONS->value,
                new QueueHighValueReviewTask(),
            );
        });
    }
}
```

`insertTaskBefore()` and `insertTaskAfter()` take a **built-in anchor task name** and your task instance. Formie logs a warning if the anchor cannot be found — double-check spelling against the [default tasks table](/developers/submission-workflow#default-tasks).

## The task class

Create `modules/formieworkflow/src/tasks/QueueHighValueReviewTask.php`. Every task implements `TaskInterface` and returns a `TaskResult` from `execute()`.

```php [modules/formieworkflow/src/tasks/QueueHighValueReviewTask.php]
<?php
namespace modules\formieworkflow\tasks;

use Craft;
use modules\formieworkflow\jobs\ReviewHighValueSubmissionJob;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

class QueueHighValueReviewTask implements TaskInterface
{
    public function getStage(): string
    {
        return Stage::DISPATCH->value;
    }

    public function getName(): string
    {
        // Extension tasks use the same `stage.handle` pattern as built-in tasks.
        return 'dispatch.queueHighValueReview';
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        $submission = $context->request->submission;
        $orderTotal = (float)($submission->getFieldValue('orderTotal') ?? 0);

        if ($orderTotal < 1000) {
            return TaskResult::continue();
        }

        Craft::$app->getQueue()->push(new ReviewHighValueSubmissionJob([
            'submissionId' => (int)$submission->id,
        ]));

        return TaskResult::continue();
    }
}
```

`WorkflowContext` gives you the current `SubmissionRequest`, the in-progress `SubmissionResponse`, and shared `taskState` if you need to pass data to a later task in the same request.

Pick a **unique** task name. Duplicate names in one stage trigger a Formie warning in the logs.

## The queue job

The task itself should stay fast — push heavy work to the queue so the visitor is not waiting on your API.

```php [modules/formieworkflow/src/jobs/ReviewHighValueSubmissionJob.php]
<?php
namespace modules\formieworkflow\jobs;

use Craft;
use craft\queue\BaseJob;
use verbb\formie\elements\Submission;

class ReviewHighValueSubmissionJob extends BaseJob
{
    public int $submissionId = 0;

    public function execute($queue): void
    {
        $submission = Submission::find()->id($this->submissionId)->one();

        if (!$submission) {
            return;
        }

        // Notify finance, write to an internal system, etc.
        Craft::info('High-value submission queued for review: ' . $submission->id, __METHOD__);
    }

    protected function defaultDescription(): ?string
    {
        return Craft::t('formie', 'Review high-value Formie submission');
    }
}
```

Because this task sits **before** `dispatch.triggerIntegrations`, the submission is already saved and the queue job can safely load it by ID. Integrations still run after your task unless you halt the workflow (see below).

## Task results

`TaskResult::continue()` means the stage keeps going — use this when work succeeded or when there is nothing to do for this request.

```php
return TaskResult::continue();
```

`TaskResult::halt(false)` stops the workflow and marks the request unsuccessful. Use this when something failed and later tasks (notifications, integrations) must not run.

```php
return TaskResult::halt(false, [
    'reason' => 'reviewQueueFailed',
]);
```

`TaskResult::halt(true)` stops the workflow but counts as success — useful when halting is expected, such as "nothing left to do for this mode".

Formie maps a task halt to a **stage halt**. The pipeline stops; later stages are skipped.

## Choosing an anchor task

Insert relative to the built-in task that marks the boundary you care about.

| Goal | Stage | Typical anchor |
| --- | --- | --- |
| Extra check before spam rules | `screen` | `screen.runSubmissionGuards` or `screen.runSpamChecks` |
| Block save when business rules fail | `authorize` | `authorize.haltOnSubmissionErrors` |
| Work after persistence, before payment | `save` | `save.persistSubmissionWorkflow` |
| Internal job before email | `dispatch` | `dispatch.sendNotifications` |
| Work before CRM / automations | `dispatch` | `dispatch.triggerIntegrations` |
| Adjust response payload | `finalize` | `finalize.hydrateResponse` |

See the full list in [Submission Workflow — default tasks](/developers/submission-workflow#default-tasks).

## When events are enough

If you only need a short side effect beside one built-in step, `afterTask` avoids a new class:

```php
use verbb\formie\enums\workflow\Task;
use verbb\formie\events\SubmissionWorkflowTaskEvent;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_AFTER_TASK, function(SubmissionWorkflowTaskEvent $event) {
    if ($event->task !== Task::DISPATCH_TRIGGER_INTEGRATIONS->value) {
        return;
    }

    // Side effect after integrations run.
});
```

Reach for a custom task when ordering matters for **multiple** extensions, you want a stable name in logs, or you need the `beforeTask` / `afterTask` events to target your logic explicitly. For several event-only patterns without new classes, see [Using submission workflow events](/guides/submissions-workflows/using-submission-workflow-events).

## Finishing up

With the module enabled:

1. Submit a form on the front end with `orderTotal` below the threshold — integrations should behave as before, and your task should no-op.
2. Submit with a value above the threshold — check `storage/logs/` for `Starting workflow task "dispatch.queueHighValueReview"` and confirm the queue job runs.
3. Save a multi-page draft — dispatch tasks (including yours) should **not** run.

Formie logs each stage and task at `info` level. If your task never appears, confirm the stage name in the event listener, the anchor task name, and that you are testing a full **submit** rather than save-and-continue.

For a whole new phase in the pipeline — not just one more step inside an existing stage — see [Adding a custom workflow stage from scratch](/guides/submissions-workflows/adding-a-custom-workflow-stage-from-scratch).

## Related

- [Submission workflow and stages explained](/guides/submissions-workflows/submission-workflow-and-stages-explained)
- [Submission Workflow](/developers/submission-workflow)
- [Submission Events](/developers/events/submission-events)
- [Integration dispatch and policies](/guides/integrations/integration-dispatch-and-policies)
