# Submission Workflow

Formie executes a resolved, authorised `SubmissionCommand` through six fixed stages. The command identifies the form, submission, operation, navigation intent and authority. Request parsing, ownership checks, request-token integrity and rate limits run in the processor before the workflow begins. Input is applied only after the operation and submission resource are locked and the expected version is checked.

Use a [custom task](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch) for ordered work that can affect execution. Use [submission events](/developers/events/submission-events) to observe a stage, task, accepted page or completed submission.

## Stages and Public Anchors

`WorkflowManifest` owns stage order, built-in task order, handlers and applicable operations. Extensions can add tasks, but cannot add, replace or reorder stages.

| Stage | Public Task Anchors |
| --- | --- |
| `preflight` | `preflight.resolveNavigationIntent`, `preflight.applySubmissionDefaults`, `preflight.clearHiddenValues`, `preflight.enforceProgression`, `preflight.resolveTransition`, `preflight.captureMetadata`, `preflight.applyStatusRules` |
| `validate` | `validate.submission` |
| `screen` | `screen.evaluateSpam`, `screen.verifyCaptcha` |
| `persist` | `persist.submission`, `persist.processPayment`, `persist.questionnaireResult` |
| `dispatch` | `dispatch.sendNotifications`, `dispatch.triggerIntegrations`, `dispatch.sendSpamNotifications` |
| `finalize` | Stage boundary only |

Preflight applies defaults and conditions before selecting the next page. Validate checks the current page or the whole submission and questionnaire retake eligibility. Validation errors stop processing before content spam checks or CAPTCHA. Persist plans the write, stores the submission, processes payment and recalculates questionnaire results. Payment submissions remain incomplete until payment succeeds. Dispatch starts the applicable notification and integration work. Finalize applies spam policy, progression, upload bookkeeping, token consumption and the terminal outcome. Transport adapters build the HTML, AJAX, REST or GraphQL response.

Persistence planning, revision follow-ups and finalisation bookkeeping are internal tasks, not public anchors. The task observation events only expose public anchors. Stage events still expose Finalize.

## Operations and Navigation

| Operation | Behaviour |
| --- | --- |
| `SubmissionOperation::SUBMIT` | Accept a visitor page or final submission, including continuation of an incomplete submission. Screen and completion dispatch run when the visitor attempts completion. |
| `SubmissionOperation::SAVE_DRAFT` | Save progress without validation, content screening or dispatch. Integrity, ownership, rate and replay checks still apply. |
| `SubmissionOperation::REVISE` | Validate and update a persisted record without visitor progression. Recalculate questionnaire results and apply configured edit integration and status-change notification policies. |
| `SubmissionOperation::PAYMENT_REPLAY` | Read the saved submission and payment state, without rebuilding or populating submitted values. Complete and dispatch when payment permits it. |

`NavigationIntent::ADVANCE`, `BACK`, `STAY` and `TARGET` describe navigation separately from the operation. Back navigation skips forward validation. Target navigation cannot skip an unvalidated intervening page.

Control panel creation uses Submit with `SubmissionPolicy::ADMINISTRATIVE_CREATE`: whole-record validation, the administrator's selected status, and no visitor progression, screening, payment or automatic completion dispatch. Administrative GraphQL mutations require schema create/save permission for the target form. Public actions always require visitor authority, including requests from logged-in administrators.

## Registering Tasks

A task implements only `execute(WorkflowContext $context): TaskResult`. Registration supplies its unique ID, handler, applicable operations and placement. Use an extension namespace for IDs, such as `acme.validateOrder`.

Place this listener in your Craft module's `init()` method, after `parent::init()`:

```php
use modules\formieworkflow\tasks\CheckOrderTask;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\events\RegisterStageTasksEvent;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\workflow\TaskDefinition;
use yii\base\Event;

Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_REGISTER_STAGE_TASKS, function(RegisterStageTasksEvent $event) {
    if ($event->stage !== Stage::VALIDATE) {
        return;
    }

    $event->insertTaskAfter(Task::VALIDATE_SUBMISSION, new TaskDefinition(
        'acme.validateOrder',
        new CheckOrderTask(),
        [SubmissionOperation::SUBMIT],
    ));
});
```

`insertTaskBefore()` and `insertTaskAfter()` accept a public `Task` enum or registered public task ID. `prepend()` and `append()` insert at stage boundaries without artificial start/end anchors. Registrations are merged deterministically. Duplicate IDs and unknown or internal anchors throw an exception. Applicable operations are mandatory; a Submit task does not automatically run on drafts or payment replay.

## Execution Results and Events

Return `TaskResult::continue()` to continue. Return a typed outcome to stop expected processing:

```php
use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\workflow\tasks\TaskResult;

$context->command->submission->addError('form', 'This order needs review.');
return TaskResult::stop($context->result(SubmissionOutcomeType::REJECTED));
```

Outcomes distinguish page changes, saved drafts, completed submissions, revisions, payment action required, payment pending, validation failure, payment failure, rejection and state conflict. Unexpected system failures remain exceptions. A stop does not roll back work already performed; put rejection checks before persistence.

Stage and task observation events carry `command`, `context`, `stage`, and, after execution, `result`. Task events also carry `task`. They are not cancellable. Use a registered task and a typed result to control execution. An after-integration task event means the dispatch intent ran; queued remote delivery may still be pending.

`EVENT_AFTER_PAGE_ADVANCE` fires after a successfully persisted forward page. `Submission::EVENT_AFTER_COMPLETE` fires when completion becomes durable, before completion dispatch. Direct Craft element saves persist the record and raise element events, but do not run the workflow or automatically dispatch notifications and integrations.

## Versions and Retries

Interactive sessions expose `version`; managed HTML/AJAX requests post it as `expectedVersion`. Administrative GraphQL edits also require `expectedVersion`, obtained from the submission's `stateVersion`. A stale write returns `stateConflict` before posted values are applied.

An operation ID identifies a retry, not permission to access a submission. Visitor receipts are scoped to the session and form; administrative receipts to the user/schema and form; payment replay receipts to the saved submission and provider operation. The processor locks the operation before the resource. Seven-day durable receipts store a keyed payload fingerprint and an encrypted outcome limited to 64 KiB before encryption. Successful duplicates recover that outcome; changed input conflicts. Validation failures leave the request token available for correction. A receipt left processing after an interrupted attempt blocks blind replay until its outcome is reconciled. Cleanup removes receipts after seven days, matching the interactive request-token lifetime.

REST and server-rendered AJAX map validation and payment failure to 422, state conflict to 409, rejection to 403 and throttling to 429. Deliberate fake-success spam policy returns 200. Interactive GraphQL returns expected workflow outcomes as data. Legacy HTML/AJAX retains its response shape and boolean success field alongside the typed outcome.
