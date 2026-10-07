# Submission Workflow

Formie's submission workflow runs validation, spam checks, saving, payments and delivery through six fixed stages. Extend it with a [custom task](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch) when your code needs to run at a particular point or stop processing. Use [submission events](/developers/events/submission-events) to observe a stage, task, accepted page or completed submission.

A `SubmissionCommand` describes the requested operation: the form and submission, where the visitor wants to go, and their permission to act. `SubmissionRequests` checks incoming HTML, client-rendered, administrative GraphQL and verified payment requests, and builds their responses. `SubmissionProcessor::executeCommand()` runs an authorised command and returns a `SubmissionOutcome`; it does not read request flags or build HTTP responses. Before applying input, the processor locks the operation and submission and checks the expected version.

Administrative GraphQL `saveSubmission` mutations are persistence-only: schema permissions, content validation, expected-version checks and upload ownership still apply, but visitor progression, screening, payment processing, redirects and delivery dispatch do not run. Administrative and workflow saves share `SubmissionPersistence`, including upload promotion and atomic completion. Public GraphQL visitor submission uses the visitor command path instead.

## Stages and Public Anchors

`WorkflowManifest` defines stage order, built-in task order, handlers and applicable operations. The public task anchors below are named positions you can use to place a custom task before or after a built-in task. Extensions can add tasks, but cannot add, replace or reorder stages.

| Stage | Public Task Anchors |
| --- | --- |
| `preflight` | `preflight.applySubmissionDefaults`, `preflight.clearHiddenValues`, `preflight.resolveTransition`, `preflight.captureMetadata`, `preflight.applyStatusRules` |
| `validate` | `validate.submission` |
| `screen` | `screen.evaluateSpam`, `screen.verifyCaptcha` |
| `persist` | `persist.submission`, `persist.processPayment`, `persist.questionnaireResult` |
| `dispatch` | `dispatch.sendNotifications`, `dispatch.triggerIntegrations`, `dispatch.sendSpamNotifications` |
| `finalize` | Stage boundary only |

Preflight applies defaults and conditions before selecting the next page. Validate checks the current page or the whole submission and questionnaire retake eligibility. Validation errors stop processing before content spam checks or CAPTCHA. Persist plans the write, stores the submission, processes payment and recalculates questionnaire results. Payment submissions remain incomplete until payment succeeds. Dispatch starts the applicable notification and integration work. Finalize applies spam policy, progression, upload bookkeeping, token consumption and the terminal outcome. Transport adapters build the HTML, AJAX, REST or GraphQL response.

Navigation-state resolution, progression enforcement, persistence planning, revision follow-ups and finalisation bookkeeping are internal tasks, not public anchors. The task observation events only expose public anchors. Stage events still expose Finalize.

## Operations and Navigation

| Operation | Behaviour |
| --- | --- |
| `SubmissionOperation::SUBMIT` | Accept a visitor page or final submission, including continuation of an incomplete submission. Screen and completion dispatch run when the visitor attempts completion. |
| `SubmissionOperation::SAVE_DRAFT` | Save progress without validation, content screening or dispatch. Integrity, ownership, rate and replay checks still apply. |
| `SubmissionOperation::REVISE` | Validate and update a persisted record without visitor progression. Recalculate questionnaire results and apply configured edit integration and status-change notification policies. |
| `SubmissionOperation::PAYMENT_REPLAY` | Read the saved submission and payment state, preserving accepted field values without reapplying defaults, forced values or Hidden value sources. Complete and dispatch when payment permits it. |

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

`insertTaskBefore()` and `insertTaskAfter()` accept a public `Task` enum or registered public task ID. `prepend()` and `append()` insert at stage boundaries without artificial start/end anchors. Formie combines registrations in a consistent order. Duplicate IDs and unknown or internal anchors throw an exception. Applicable operations are mandatory; a Submit task does not automatically run on drafts or payment replay.

## Execution Results and Events

Return `TaskResult::continue()` to continue. Return a typed outcome to stop expected processing:

```php
use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\workflow\tasks\TaskResult;

$context->command->submission->addError('form', 'This order needs review.');
return TaskResult::stop($context->result(SubmissionOutcomeType::REJECTED));
```

Outcomes distinguish page changes, saved drafts, completed submissions, revisions, payment action required, payment pending, validation failure, payment failure, rejection and state conflict. Unexpected system failures remain exceptions. A stop does not roll back work already performed; put rejection checks before persistence.

`WorkflowContext::result()` constructs an outcome from the current submission and any data you supply. It does not resolve completion settings, raise completion-resolution events, or save metadata. The built-in Finalize task resolves and stores completion details for a completed submission. Return `TaskResult::continue()` from a custom task when you want the workflow to reach that finalisation; returning a completed outcome early stops the workflow at your task.

Stage and task observation events carry `command`, `context`, `stage`, and, after execution, `result`. Task events also carry `task`. They are not cancellable. Use a registered task and a typed result to control execution. An after-integration task event means the task requested integration delivery; queued delivery may still be pending.

Notification and integration queue jobs implement `verbb\formie\jobs\DeliveryJobInterface`. Queue observers can test this interface and call `getDeliveryAttemptUid()` to find the stored delivery attempt for a job. The getter leaves the serialised job unchanged. Store diagnostic details with the delivery attempt rather than adding them to the queue job.

`EVENT_AFTER_PAGE_ADVANCE` fires after a successfully persisted forward page. Within the workflow, `Submission::EVENT_AFTER_COMPLETE` fires after completion is saved, before completion dispatch. Direct Craft element saves persist the record and raise element events, but do not run the workflow or automatically dispatch notifications and integrations.

Completion also saves a pending `SubmissionDispatch` in the same database transaction. This record identifies one delivery run: payment replay reuses the completion run, while accepted revisions and deliberate manual deliveries get separate runs. Its ID is separate from the browser request token.

Queue jobs and provider requests start after that transaction commits. If processing is interrupted, scheduled recovery runs only the registered Dispatch tasks, using the same run ID. It does not repeat persistence, payment or completion events. Custom Dispatch tasks must handle retries without repeating external actions that have already succeeded.

The dispatch status distinguishes scheduled work from running work, completed delivery, completed delivery with failures and work needing attention. A delivery attempt describes one operation within that run. See [delivery recovery commands](/developers/console-commands#recover-interrupted-submission-delivery) for scheduling and inspection.

## Versions and Retries

Interactive sessions expose `version`; managed HTML/AJAX requests post it as `expectedVersion`. Administrative GraphQL edits also require `expectedVersion`, obtained from the submission's `stateVersion`. A stale write returns `stateConflict` before posted values are applied.

An operation ID identifies a request and its retries; it does not grant access to a submission. Formie saves an operation receipt so it can return the previous outcome when a request is repeated. Visitor receipts belong to a session and form, administrative receipts to a user/schema and form, and payment replay receipts to a saved submission and provider operation. The processor locks the operation before the submission.

Receipts last seven days and contain a keyed fingerprint of the input and an encrypted outcome, limited to 64 KiB before encryption. Repeating a successful request with the same input returns the saved outcome; changing the input returns a conflict. Validation failures leave the request token available for correction. If an interrupted request leaves a receipt in the processing state, reconcile its outcome before retrying. Cleanup removes receipts after seven days, matching the interactive request-token lifetime.

REST and server-rendered AJAX map validation and payment failure to 422, state conflict to 409, rejection to 403 and throttling to 429. Deliberate fake-success spam policy returns 200. Interactive GraphQL returns expected workflow outcomes as data. Legacy HTML/AJAX retains its response shape and boolean success field alongside the typed outcome.

## Progress, Grants and Uploads

An incomplete `Submission` owns its saved field content. `SubmissionProgress` contains the current page, expiry and version; provisional content is allowed only before a submission exists. The browser session refers to this saved progress record. A progress ID, submission ID or UID does not authorise access.

Ordinary page navigation lets the same browser continue the form. **Save & Continue** explicitly issues a portable `continue-incomplete` grant. Completed records require a separate `revise-complete` grant and the `Revise` operation. Grants store a versioned hash, target/form/site, purpose, expiry and revocation metadata. Exchanging a portable grant associates another browser with the same record; it does not move the original browser's state.

Use `Formie::$plugin->getSubmissionGrants()` to issue, exchange, rotate or revoke grants from trusted PHP. Authorise the visitor before issuing a grant. Keep the returned `SubmissionGrant::token` only in the issued response or private link. `verify()` returns metadata without reconstructing the token. Revocation of a portable grant also invalidates browser bindings derived from it; `revokeSubmission()` can revoke every grant for a target. Continue grants stop working when the submission completes or disappears. Expired, revoked and unavailable targets use the same public failure response.

Client bootstrap accepts `grantToken`, `grantPurpose` and an optional `draftContext` in both REST and `formieClientForm`. The core REST/GraphQL loaders accept these same options and remove a matching bearer query parameter from browser history after successful exchange. Custom clients must do the same. Exchange credentials over HTTPS and omit them from analytics. The returned session contains a browser-bound progress reference or revision target. Post the returned session, including its version, with the next request. A revision session selects `Revise`; it cannot be used to continue an incomplete submission. An explicit client `save` returns `resumeToken`, `resumeUrl` and `resumeTokenExpiresAt` outside the browser session. Ordinary navigation never returns a portable grant. Refresh preserves the revision purpose and rechecks its live binding.

Formie tracks uploads as staged (uploaded temporarily), bound (attached to an incomplete submission) or finalised (accepted on completion). Final binding checks the exact form, field path, site and owner before persistence. Upload creation returns `uploadUid`, `attachToken`, `uploadToken` (view) and `deleteToken`. Client/GraphQL upload values can use `{ uploadUid, attachToken }`. Asset-ID values are accepted only when the server can prove ownership of the staged upload or the authorised submission's existing field relation.

Base64 uploads sent through client or GraphQL input are bounded before decoding by Craft's `maxUploadFileSize`. If that setting has no positive limit, the decoder uses a 16 MiB ceiling. Field-specific limits still apply when the submission is validated.

Async, native workflow and client/GraphQL uploads share a locked staging budget across fields in the same browser/form instance. Configure `maxStagedUploadFiles` and `maxStagedUploadBytes` for forms with larger legitimate workloads. Bound incomplete uploads and expired files waiting for cleanup still count; successful deletion or completion releases the budget. Staged expiry is capped at 30 days even when submission retention is unlimited.

Staged upload responses provide a preview URL protected by a temporary view token instead of the filesystem URL. A valid view token is required to read the file; attach and delete tokens cannot read its contents. Preview responses prevent caching and referrer forwarding, display supported raster images inline and download other files. Treat these URLs as temporary credentials and exclude their query strings from access logs and analytics. Staging requires a temporary asset filesystem without public URLs; its underlying storage must also be private. Completed or legacy assets retain the visibility of their configured destination volume.

When a submission is becoming complete, promotion runs within Persist after an incomplete submission has been saved. Native multipart/base64 files are staged before the submission transaction. Sorted upload locks protect binding through persistence and promotion; cleanup skips active claims, and recovery serializes moves using the same locks. A durable exact destination, source identity, content fingerprint and `moving` marker precede the filesystem/provider operation; success records `moved`. Provider failure preserves a bounded failure code and leaves that submission incomplete without raising completion events or entering Dispatch. Once promotion succeeds, completion and accepted-upload finalization are committed together. Payment-pending submissions keep bound uploads, and PaymentReplay checks promotion before completing them. Ordinary submissions without uploads retain a single element save.

After diagnosing the provider failure, trusted maintenance code can call `getFileUploads()->recoverPromotions($submissionId)` to finish outstanding moves. If a provider completed a move before Craft committed its asset metadata, recovery verifies the destination's content fingerprint and repairs that metadata instead of repeating the move. Recovery alone does not complete the submission, replay payments or send notifications; reconcile the interrupted command receipt separately before resuming those effects.

File retention removes only the expired field relation when a file is shared or ownership cannot be proved. Failed physical deletion keeps the tracking record and any retryable field references. Permanent submission deletion preserves owned cleanup records after removing the submission, so later cleanup can retry failed file deletion. References on restorable trashed submissions also protect their files.

If the deleted submission had an interrupted promotion, cleanup checks the recorded destination fingerprint before removing it. A different file at that location is left untouched and the cleanup record remains available for reconciliation; expiry does not discard that evidence.

Scheduled cleanup expires grants and abandoned progress/uploads. Referenced assets and valid bound/finalized uploads are protected from abandoned-upload cleanup. An interrupted claim that never persisted a submission reference can expire after its lease; removal or replacement of a field value releases only unreferenced uploads. Files shared by other submissions remain protected. Grant expiry is capped by progress and target-submission retention, including form data retention. Upload view, attach and delete capabilities expire with their upload record; rendering refreshes capabilities only while the record remains available. These records contain sensitive scope and lifecycle metadata: restrict database and maintenance access to administrators, and use the configured retention settings rather than exporting them to application logs.

Preflight applies defaults, clears hidden values, enforces progression and resolves the transition in that order. Validate consumes that transition to choose current-page or whole-submission validation; it does not resolve the route again. A failed validation stops before Screen, persistence or page advancement. See [Conditions and Validation](/developers/conditions-and-validation) for visibility, clearing, scopes and error identity.
