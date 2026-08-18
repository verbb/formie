# Adding a custom workflow stage from scratch

Sometimes one more task inside `screen` or `dispatch` is not enough — you need a **new phase** in the pipeline with its own boundary in the logs, its own before/after stage events, and a clear place in the ordering between built-in stages.

This walkthrough registers a **fraud score** stage that runs after Formie's spam screening and before authorization and save. A low score marks the submission as spam; a service failure halts the request so nothing is dispatched.

Read [Submission workflow and stages explained](/guides/submissions-workflows/submission-workflow-and-stages-explained) and [Adding a custom workflow task from scratch](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch) first — custom stages still use tasks internally, and the task guide covers `TaskInterface` and `TaskResult` in detail.

## When a custom stage fits

| Approach | Use when |
| --- | --- |
| `EVENT_AFTER_PAGE_ADVANCE` / `EVENT_AFTER_COMPLETE` | Page submit, or the form actually submitted — not a new stage. |
| Custom task in an existing stage | One ordered step inside `screen`, `save`, `dispatch`, etc. |
| **Custom stage** | A new lifecycle phase — fraud scoring, enrichment, compliance hold — with its own stage name and position in the pipeline. |
| `beforeStage` / `afterStage` on a built-in stage | Lightweight observation without registering new pipeline structure. |

Custom stages are inserted into Formie's stage registry. They run on **every** request that reaches that point in the pipeline unless you guard on `processMode` inside the stage — save-and-continue requests still walk through later stages even when most built-in tasks inside them are skipped.

## Create your module

Use the same [Craft module](https://craftcms.com/docs/5.x/extend/module-guide.html) pattern as the task guide. Namespace `modules\formieworkflow`, module ID `formie-workflow`:

```treeview
my-project/
├── modules/
│   └── formieworkflow/
│       └── src/
│           ├── stages/
│           │   └── CheckFraudScoreStage.php
│           ├── tasks/
│           │   └── CheckFraudScoreTask.php
│           └── FormieWorkflow.php
└── ...
```

Register the stage in `FormieWorkflow.php`. If you already registered a custom task from the [task guide](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch), add this listener alongside it.

```php [modules/formieworkflow/src/FormieWorkflow.php]
<?php
namespace modules\formieworkflow;

use modules\formieworkflow\stages\CheckFraudScoreStage;
use verbb\formie\events\RegisterWorkflowStagesEvent;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;
use yii\base\Module;

class FormieWorkflow extends Module
{
    public function init(): void
    {
        parent::init();

        Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_REGISTER_WORKFLOW_STAGES, function(RegisterWorkflowStagesEvent $event) {
            $event->insertStageAfter('screen', new CheckFraudScoreStage(
                SubmissionWorkflow::instance(),
            ));
        });
    }
}
```

`insertStageBefore()` and `insertStageAfter()` anchor on a **built-in stage name** — `prepare`, `normalize`, `validate`, `screen`, `authorize`, `save`, `dispatch`, or `finalize`. The fraud stage belongs after `screen` so captcha and keyword rules run first; adjust the anchor for your use case.

## The stage class

A stage implements `StageInterface` and returns a `StageResult` from `execute()`. Most custom stages delegate to `SubmissionWorkflow::runStageTasks()` so you get task registration events and consistent logging — the same machinery built-in stages use.

```php [modules/formieworkflow/src/stages/CheckFraudScoreStage.php]
<?php
namespace modules\formieworkflow\stages;

use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\workflow\StageInterface;
use verbb\formie\workflow\StageResult;
use verbb\formie\workflow\WorkflowContext;
use modules\formieworkflow\tasks\CheckFraudScoreTask;

class CheckFraudScoreStage implements StageInterface
{
    public function __construct(private SubmissionWorkflow $workflow)
    {
    }

    public function getName(): string
    {
        return 'fraudScore';
    }

    public function execute(WorkflowContext $context): StageResult
    {
        if ($context->request->processMode !== SubmissionWorkflow::PROCESS_MODE_SUBMIT) {
            return StageResult::continue();
        }

        return $this->workflow->runStageTasks($context, $this->getName(), [
            new CheckFraudScoreTask(),
        ]);
    }
}
```

The `processMode` guard is important for custom stages. Unlike extension tasks inserted into built-in stages, a custom stage's `execute()` method is called whenever the pipeline reaches that slot — including save-and-continue and payment replay paths. Return early when the stage should only run on a full public submit.

## The task class

Keep the API call in a task so you can add more steps later without rewriting the stage.

```php [modules/formieworkflow/src/tasks/CheckFraudScoreTask.php]
<?php
namespace modules\formieworkflow\tasks;

use Craft;
use craft\helpers\App;
use Throwable;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

class CheckFraudScoreTask implements TaskInterface
{
    public function getStage(): string
    {
        return 'fraudScore';
    }

    public function getName(): string
    {
        return 'fraudScore.check';
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        $submission = $context->request->submission;

        if ($submission->isSpam) {
            return TaskResult::continue();
        }

        try {
            $client = Craft::createGuzzleClient();
            $response = $client->post(App::env('FRAUD_SCORE_URL'), [
                'json' => [
                    'email' => $submission->getFieldValue('email'),
                    'ip' => $submission->ipAddress,
                ],
            ]);

            $score = (float)json_decode((string)$response->getBody(), true)['score'];

            if ($score >= 0.8) {
                $submission->isSpam = true;
                $submission->spamReason = 'fraudScoreHigh';

                return TaskResult::continue();
            }
        } catch (Throwable $e) {
            Craft::error('Fraud score check failed: ' . $e->getMessage(), __METHOD__);

            return TaskResult::halt(false, [
                'reason' => 'fraudScoreUnavailable',
            ]);
        }

        return TaskResult::continue();
    }
}
```

Two different outcomes:

- **Mark as spam** — set `$submission->isSpam` and return `TaskResult::continue()`. Later stages still run; Formie's finalize behaviour applies your form's spam handling settings.
- **Hard failure** — return `TaskResult::halt(false)` when the service is down and you must not save or dispatch. The workflow stops; the visitor sees an error response.

Tasks inside a **custom** stage always run when the stage executes. Built-in mode filtering applies to built-in task names only.

## How the pipeline changes

After registration, a full submit looks like:

```
Browser POST
    → prepare
    → normalize
    → validate
    → screen (guards, captcha, spam)
    → fraudScore (your stage)
    → authorize
    → save
    → dispatch
    → finalize
    → Browser response
```

`beforeStage` and `afterStage` listeners can target `'fraudScore'` by name. `beforeTask` / `afterTask` listeners can target `'fraudScore.check'`.

## Stage and task results

Stages use `StageResult`; tasks use `TaskResult`. When a task returns `halt`, Formie converts it to a stage halt and stops the pipeline.

| Return | Effect |
| --- | --- |
| `TaskResult::continue()` | Next task in the stage, then the next stage. |
| `TaskResult::halt(false)` | Workflow stops; submission marked unsuccessful. |
| `TaskResult::halt(true)` | Workflow stops; counts as success (rare for screening). |
| `StageResult::continue()` | Skip the stage's tasks entirely — use for process-mode guards. |

You can also implement `execute()` without `runStageTasks()` when the stage is a single block of logic and you do not need task-level events.

## Finishing up

1. Set `FRAUD_SCORE_URL` in `.env` and submit a clean test entry — the request should pass through to save and dispatch.
2. Simulate a high score — confirm the submission is stored according to your spam handling settings and integrations respect spam state.
3. Simulate API failure — confirm the submission does not dispatch notifications or integrations.
4. Save a multi-page draft — with the `processMode` guard in place, the fraud stage should no-op immediately.

Watch `storage/logs/` for `Starting workflow stage "fraudScore"` and `Starting workflow task "fraudScore.check"`.

If the stage never runs, confirm the anchor stage name in `insertStageAfter()`. If the task never runs, confirm the stage's `execute()` method is not returning early and that `runStageTasks()` is called with the correct stage name.

## Related

- [Run custom code on page submit or form submit](/guides/submissions-workflows/run-custom-code-on-page-submit-or-form-submit)
- [Using submission workflow events](/guides/submissions-workflows/using-submission-workflow-events)
- [Adding a custom workflow task from scratch](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch)
- [Submission workflow and stages explained](/guides/submissions-workflows/submission-workflow-and-stages-explained)
- [Submission screening rules in practice](/guides/submissions-workflows/submission-screening-rules-in-practice)
- [Submission Workflow](/developers/submission-workflow)
- [Submission Events](/developers/events/submission-events)
