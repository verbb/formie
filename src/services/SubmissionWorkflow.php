<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\events\RegisterWorkflowStagesEvent;
use verbb\formie\events\RegisterStageTasksEvent;
use verbb\formie\events\SubmissionCompleteEvent;
use verbb\formie\events\SubmissionPageAdvanceEvent;
use verbb\formie\events\SubmissionRequestEvent;
use verbb\formie\events\SubmissionWorkflowStageEvent;
use verbb\formie\events\SubmissionWorkflowTaskEvent;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\SubmissionResponse;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\workflow\StageRegistry;
use verbb\formie\workflow\StageInterface;
use verbb\formie\workflow\StageResult;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\WorkflowPolicy;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\stages\AuthorizeStage;
use verbb\formie\workflow\stages\DispatchStage;
use verbb\formie\workflow\stages\FinalizeStage;
use verbb\formie\workflow\stages\NormalizeStage;
use verbb\formie\workflow\stages\PrepareStage;
use verbb\formie\workflow\stages\SaveStage;
use verbb\formie\workflow\stages\ScreenStage;
use verbb\formie\workflow\stages\ValidateStage;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

use Craft;
use craft\helpers\Json;

use yii\base\Component;
use yii\web\BadRequestHttpException;

class SubmissionWorkflow extends Component
{
    // Constants
    // =========================================================================

    public const PROCESS_MODE_SUBMIT = 'submit';
    public const PROCESS_MODE_EDIT_EXISTING = 'editExisting';
    public const PROCESS_MODE_SAVE_DRAFT = 'saveDraft';
    public const PROCESS_MODE_PAYMENT_REPLAY = 'paymentReplay';

    public const SUBMIT_ACTION_SUBMIT = 'submit';
    public const SUBMIT_ACTION_BACK = 'back';
    public const SUBMIT_ACTION_SAVE = 'save';

    public const EVENT_BEFORE_SET_PAGE = 'beforeSetPage';
    public const EVENT_AFTER_SET_PAGE = 'afterSetPage';
    public const EVENT_REGISTER_WORKFLOW_STAGES = 'registerWorkflowStages';
    public const EVENT_REGISTER_STAGE_TASKS = 'registerStageTasks';
    public const EVENT_BEFORE_STAGE = 'beforeStage';
    public const EVENT_AFTER_STAGE = 'afterStage';
    public const EVENT_BEFORE_TASK = 'beforeTask';
    public const EVENT_AFTER_TASK = 'afterTask';

    /**
     * Fired after a successful save when the visitor advanced to another page
     * (Next). Does not fire on Back, save-and-continue, or final submit.
     */
    public const EVENT_AFTER_PAGE_ADVANCE = 'afterPageAdvance';


    // Static Methods
    // =========================================================================

    public static function getAllowedSubmitActions(): array
    {
        return [
            self::SUBMIT_ACTION_SUBMIT,
            self::SUBMIT_ACTION_BACK,
            self::SUBMIT_ACTION_SAVE,
        ];
    }


    // Public Methods
    // =========================================================================

    public function processSubmissionRequest(SubmissionRequest $request): SubmissionResponse
    {
        // All submission entry surfaces collapse into the same stage engine. The
        // request decides which tasks are enabled, but stage ordering and stage
        // events stay consistent across controller, managed-client, mutation, and
        // payment-replay execution.
        $workflow = $this->_getSubmitWorkflow($request);
        $context = new WorkflowContext($request, $workflow);
        WorkflowContext::push($context);

        try {
            $stages = $this->_createWorkflowStages();
            $context = $this->_runWorkflowStages($context, $stages);
        } finally {
            WorkflowContext::pop();
        }

        if (!$context->success) {
            $submissionErrors = $request->submission->getErrors();
            $haltedStage = $context->workflowExecution->haltedAtStage;
            $haltedStageResult = $haltedStage ? ($context->workflowExecution->stageResults[$haltedStage] ?? null) : null;
            $paymentDecision = $context->taskState['payment.decision'] ?? null;

            if (is_object($paymentDecision) && method_exists($paymentDecision, 'toArray')) {
                $paymentDecision = $paymentDecision->toArray();
            }

            Formie::info('Couldn’t save submission due to workflow errors - {e}.', ['e' => Json::encode($submissionErrors)]);
            Formie::warning('Workflow failure details: haltedStage="{stage}" stageMeta={meta} paymentStatus="{paymentStatus}" paymentMessage="{paymentMessage}" paymentDecision={paymentDecision}.', [
                'stage' => $haltedStage ?? '',
                'meta' => Json::encode($haltedStageResult?->meta ?? []),
                'paymentStatus' => (string)($context->response->paymentStatus ?? ''),
                'paymentMessage' => (string)($context->response->paymentMessage ?? ''),
                'paymentDecision' => Json::encode($paymentDecision ?? []),
            ]);
        }

        $context->response->success = $context->success;
        $context->response->nextPage = $context->nextPage;
        $context->response->form = $request->form;
        $context->response->submission = $request->submission;
        $context->response->workflowResult = [
            'success' => $context->workflowExecution->success,
            'halted' => $context->workflowExecution->halted,
            'haltedAtStage' => $context->workflowExecution->haltedAtStage,
            'stages' => array_map(static fn($result) => [
                'success' => $result->success,
                'halt' => $result->halt,
                'meta' => $result->meta,
            ], $context->workflowExecution->stageResults),
        ];

        return $context->response;
    }

    public function setPageNavigationState(Form $form, ?int $pageId, ?int $submissionId = null): void
    {
        if (!$pageId) {
            return;
        }

        $submission = null;

        if ($submissionId) {
            $submission = Submission::find()
                ->id($submissionId)
                ->isIncomplete(true)
                ->status(null)
                ->one();
        }

        $page = $this->_findPageById($form, $pageId);

        if (!$page) {
            return;
        }

        $stateSubmission = $submission ?? new Submission();
        $stateSubmission->setForm($form);

        $request = new SubmissionRequest([
            'processMode' => self::PROCESS_MODE_SUBMIT,
            'form' => $form,
            'submission' => $stateSubmission,
            'submitAction' => self::SUBMIT_ACTION_SAVE,
            'pageId' => $pageId,
        ]);

        $beforeEvent = $this->triggerRequestEvent(self::EVENT_BEFORE_SET_PAGE, $request);

        if (!$beforeEvent->isValid) {
            return;
        }

        Formie::$plugin->getSubmissionDrafts()->upsertPageState($form, $pageId, $submission?->id ? (int)$submission->id : null);
        $form->setCurrentPage($page);
        $form->setCurrentSubmission($stateSubmission);

        $this->triggerRequestEvent(self::EVENT_AFTER_SET_PAGE, $request);
    }

    public function triggerRequestEvent(string $eventName, SubmissionRequest $request): SubmissionRequestEvent
    {
        $event = new SubmissionRequestEvent([
            'request' => $request,
        ]);

        $this->trigger($eventName, $event);

        return $event;
    }

    public function resolveStageTasks(string $stage, array $tasks): array
    {
        $event = new RegisterStageTasksEvent([
            'stage' => $stage,
            'tasks' => $tasks,
        ]);
        $this->trigger(self::EVENT_REGISTER_STAGE_TASKS, $event);

        $taskNames = array_map(static fn(TaskInterface $task) => $task->getName(), $event->tasks);
        $duplicateTaskNames = array_unique(array_diff_assoc($taskNames, array_unique($taskNames)));

        if ($duplicateTaskNames) {
            Formie::warning('Duplicate workflow task names detected in stage "{stage}": {tasks}', [
                'stage' => $stage,
                'tasks' => implode(', ', $duplicateTaskNames),
            ]);
        }

        return $event->tasks;
    }

    public function runStageTasks(WorkflowContext $context, string $stage, array $tasks): StageResult
    {
        $tasks = $this->resolveStageTasks($stage, $tasks);
        $stageSuccess = true;

        foreach ($tasks as $task) {
            if (!$this->_shouldRunTask($context, $stage, $task->getName())) {
                continue;
            }

            Formie::info('Starting workflow task "{task}" in stage "{stage}".', [
                'stage' => $stage,
                'task' => $task->getName(),
            ]);

            $beforeEvent = new SubmissionWorkflowTaskEvent([
                'context' => $context,
                'request' => $context->request,
                'stage' => $stage,
                'task' => $task->getName(),
            ]);
            $this->trigger(self::EVENT_BEFORE_TASK, $beforeEvent);

            if (!$beforeEvent->isValid) {
                // A before-task veto is authoritative for the whole stage. Once
                // an observer blocks a task we halt immediately so later tasks
                // do not run against assumptions that the veto invalidated.
                return StageResult::halt(false, [
                    'reason' => 'beforeTaskInvalid',
                    'task' => $task->getName(),
                ]);
            }

            $taskResult = $task->execute($context);

            $afterEvent = new SubmissionWorkflowTaskEvent([
                'context' => $context,
                'request' => $context->request,
                'stage' => $stage,
                'task' => $task->getName(),
                'result' => $taskResult,
            ]);
            $this->trigger(self::EVENT_AFTER_TASK, $afterEvent);

            Formie::info('Completed workflow task "{task}" in stage "{stage}" (success: {success}, halt: {halt}).', [
                'stage' => $stage,
                'task' => $task->getName(),
                'success' => $taskResult->success ? 'true' : 'false',
                'halt' => $taskResult->halt ? 'true' : 'false',
            ]);

            if (!$taskResult->success || $taskResult->halt) {
                $taskReason = (string)($taskResult->meta['reason'] ?? '');
                $taskMetaLogMethod = ($taskResult->success && $taskResult->halt && $taskReason === 'completed') ? 'info' : 'warning';
                Formie::{$taskMetaLogMethod}('Workflow task "{task}" emitted meta: {meta}.', [
                    'task' => $task->getName(),
                    'meta' => Json::encode($taskResult->meta),
                ]);
            }

            if (!$taskResult->success) {
                $stageSuccess = false;
            }

            if ($taskResult->halt) {
                return StageResult::halt($taskResult->success, array_merge($taskResult->meta, [
                    'task' => $task->getName(),
                ]));
            }
        }

        return new StageResult($stageSuccess, false);
    }


    // Private Methods
    // =========================================================================

    private function _createWorkflowStages(): array
    {
        // The stage order is the canonical submission lifecycle. Reordering
        // here changes where events fire, when state persists, and how payment
        // or validation failures short-circuit the request.
        $registry = new StageRegistry([
            new PrepareStage($this),
            new NormalizeStage($this),
            new ValidateStage($this),
            new ScreenStage($this),
            new AuthorizeStage($this),
            new SaveStage($this),
            new DispatchStage($this),
            new FinalizeStage($this),
        ]);

        $event = new RegisterWorkflowStagesEvent([
            'stages' => $registry->all(),
        ]);
        $this->trigger(self::EVENT_REGISTER_WORKFLOW_STAGES, $event);

        return $event->stages;
    }

    private function _runWorkflowStages(WorkflowContext $context, array $stages): WorkflowContext
    {
        foreach ($stages as $stage) {
            if ($context->halted) {
                break;
            }

            $stageName = $stage->getName();
            Formie::info('Starting workflow stage "{stage}".', ['stage' => $stageName]);

            $beforeEvent = new SubmissionWorkflowStageEvent([
                'context' => $context,
                'request' => $context->request,
                'stage' => $stageName,
            ]);
            $this->trigger(self::EVENT_BEFORE_STAGE, $beforeEvent);

            if (!$beforeEvent->isValid) {
                $stageResult = StageResult::halt(false, ['reason' => 'beforeStageInvalid']);
                $context->halt(false);
                $context->workflowExecution->addStageResult($stageName, $stageResult);
                $context->workflowExecution->halted = true;
                $context->workflowExecution->haltedAtStage = $stageName;
                $context->workflowExecution->success = false;

                break;
            }

            $stageResult = $stage->execute($context);

            if ($stageResult->halt) {
                $context->halt($stageResult->success);
            } else if (!$stageResult->success) {
                // Stage failure without halt means "record the failure, but let
                // later stages decide whether they can still produce a coherent
                // response". Hard stops are opt-in through `halt`.
                $context->success = false;
            }

            Formie::info('Completed workflow stage "{stage}" (success: {success}, halt: {halt}).', [
                'stage' => $stageName,
                'success' => $stageResult->success ? 'true' : 'false',
                'halt' => $stageResult->halt ? 'true' : 'false',
            ]);

            if (!$stageResult->success || $stageResult->halt) {
                $stageReason = (string)($stageResult->meta['reason'] ?? '');
                $stageMetaLogMethod = ($stageResult->success && $stageResult->halt && $stageReason === 'completed') ? 'info' : 'warning';
                Formie::{$stageMetaLogMethod}('Workflow stage "{stage}" emitted meta: {meta}.', [
                    'stage' => $stageName,
                    'meta' => Json::encode($stageResult->meta),
                ]);
            }

            $afterEvent = new SubmissionWorkflowStageEvent([
                'context' => $context,
                'request' => $context->request,
                'stage' => $stageName,
                'result' => $stageResult,
            ]);
            $this->trigger(self::EVENT_AFTER_STAGE, $afterEvent);

            if (!$stageResult->success) {
                Formie::warning('Workflow stage "{stage}" reported failure.', ['stage' => $stageName]);
            }

            $context->workflowExecution->addStageResult($stageName, $stageResult);

            if ($stageResult->halt) {
                $context->workflowExecution->halted = true;
                $context->workflowExecution->haltedAtStage = $stageName;
                $context->workflowExecution->success = $stageResult->success;
            }

            // Intent events fire after a successful save, before dispatch, so
            // listeners can mutate status and still affect notifications.
            if ($stageName === Stage::SAVE->value && !$stageResult->halt && $stageResult->success) {
                $this->_raiseSaveStageLifecycleEvents($context);
            }
        }

        if (!$context->workflowExecution->halted) {
            $context->workflowExecution->success = $context->success;
        }

        return $context;
    }

    private function _getSubmitWorkflow(SubmissionRequest $request): WorkflowPolicy
    {
        $mode = (string)$request->processMode;
        $modeTasks = $this->_getModeTasks();
        $settings = Formie::$plugin->getSettings();

        if (!isset($modeTasks[$mode])) {
            throw new BadRequestHttpException('Unsupported submission process mode: ' . $mode);
        }

        if ($request->submitAction === self::SUBMIT_ACTION_BACK && $settings->enableBackSubmission) {
            // Back navigation intentionally reuses the save-draft task graph so
            // page/state changes remain non-destructive and skip final-submit
            // side effects such as dispatching integrations or notifications.
            return WorkflowPolicy::fromTasks($request, $modeTasks[self::PROCESS_MODE_SAVE_DRAFT]);
        }

        return WorkflowPolicy::fromTasks($request, $modeTasks[$mode]);
    }

    private function _getModeTasks(): array
    {
        $allTasks = $this->_allTaskValues();

        return [
            // Full submit runs the canonical task graph, including screening,
            // workflow-aware persistence, dispatch, and finalization.
            self::PROCESS_MODE_SUBMIT => array_values(array_filter($allTasks, static fn(string $taskName) => $taskName !== Task::SAVE_PERSIST_SUBMISSION_DIRECT->value)),
            // Edit-existing skips spam/captcha and notifications, but can re-run
            // integrations that opt in via integration re-run policies on editExisting.
            self::PROCESS_MODE_EDIT_EXISTING => [
                Task::PREPARE_APPLY_DRAFT_CONTEXT->value,
                Task::PREPARE_INITIALIZE_SUBMIT_REQUEST->value,
                Task::NORMALIZE_HANDLE_BACK_NAVIGATION->value,
                Task::NORMALIZE_RESOLVE_PAGE_FLOW->value,
                Task::NORMALIZE_ENSURE_SUBMISSION_DEFAULTS->value,
                Task::VALIDATE_SUBMISSION->value,
                Task::AUTHORIZE_HALT_ON_SUBMISSION_ERRORS->value,
                Task::AUTHORIZE_RESOLVE_PAYMENT_STATE->value,
                Task::SAVE_PERSIST_SUBMISSION_DIRECT->value,
                Task::SAVE_PROCESS_PAYMENTS->value,
                Task::SAVE_APPLY_COMPLETION_FROM_PAYMENT_STATE->value,
                Task::SAVE_SET_PROCESSING_SUCCESS->value,
                Task::DISPATCH_GUARD_DISPATCH_ELIGIBILITY->value,
                Task::DISPATCH_TRIGGER_INTEGRATIONS->value,
                Task::FINALIZE_APPLY_PROGRESSION_STATE->value,
                Task::FINALIZE_HYDRATE_RESPONSE->value,
            ],
            // Save-draft keeps page-flow and persistence behavior, but omits the
            // validation/screening/dispatch work that only makes sense for an
            // attempt to actually complete the submission.
            self::PROCESS_MODE_SAVE_DRAFT => [
                Task::PREPARE_APPLY_DRAFT_CONTEXT->value,
                Task::PREPARE_INITIALIZE_SUBMIT_REQUEST->value,
                Task::NORMALIZE_HANDLE_BACK_NAVIGATION->value,
                Task::NORMALIZE_RESOLVE_PAGE_FLOW->value,
                Task::NORMALIZE_CLEAR_CONDITIONALLY_HIDDEN_FIELDS->value,
                Task::NORMALIZE_ENSURE_SUBMISSION_DEFAULTS->value,
                Task::AUTHORIZE_HALT_ON_SUBMISSION_ERRORS->value,
                Task::AUTHORIZE_RESOLVE_PAYMENT_STATE->value,
                Task::SAVE_PERSIST_SUBMISSION_DIRECT->value,
                Task::SAVE_SET_PROCESSING_SUCCESS->value,
                Task::FINALIZE_APPLY_PROGRESSION_STATE->value,
                Task::FINALIZE_HYDRATE_RESPONSE->value,
            ],
            // Payment replay resumes at the point where a provider callback has
            // already done the customer interaction. It re-enters the latter half
            // of the workflow so persistence, dispatch, spam behavior, and final
            // response shaping stay identical to a normal successful submit.
            self::PROCESS_MODE_PAYMENT_REPLAY => [
                Task::AUTHORIZE_RESOLVE_PAYMENT_STATE->value,
                Task::SAVE_PROCESS_PAYMENTS->value,
                Task::SAVE_PERSIST_SUBMISSION_WORKFLOW->value,
                Task::SAVE_APPLY_COMPLETION_FROM_PAYMENT_STATE->value,
                Task::SAVE_SET_PROCESSING_SUCCESS->value,
                Task::DISPATCH_GUARD_DISPATCH_ELIGIBILITY->value,
                Task::DISPATCH_SEND_NOTIFICATIONS->value,
                Task::DISPATCH_TRIGGER_INTEGRATIONS->value,
                Task::DISPATCH_SEND_SPAM_NOTIFICATIONS->value,
                Task::DISPATCH_MARK_DISPATCH_FINALIZED->value,
                Task::FINALIZE_APPLY_SPAM_BEHAVIOUR->value,
                Task::FINALIZE_APPLY_PROGRESSION_STATE->value,
                Task::FINALIZE_HYDRATE_RESPONSE->value,
            ],
        ];
    }

    private function _allTaskValues(): array
    {
        return array_map(static fn(Task $task) => $task->value, Task::cases());
    }

    private function _shouldRunTask(WorkflowContext $context, string $stage, string $taskName): bool
    {
        if ($this->_isBuiltInTask($taskName)) {
            return $context->workflow->isTaskEnabled($taskName);
        }

        // Extension tasks registered into a built-in stage inherit that stage's
        // mode matrix. Tasks inside a custom stage always run when the stage
        // executes — the stage author decides whether to enter the stage.
        if ($this->_isBuiltInStage($stage)) {
            return $this->_isStageActiveForMode($stage, $context->workflow);
        }

        return true;
    }

    private function _isBuiltInTask(string $taskName): bool
    {
        return in_array($taskName, $this->_allTaskValues(), true);
    }

    private function _isBuiltInStage(string $stageName): bool
    {
        static $builtInStages = null;

        $builtInStages ??= array_map(static fn(Stage $stage) => $stage->value, Stage::cases());

        return in_array($stageName, $builtInStages, true);
    }

    private function _isStageActiveForMode(string $stage, WorkflowPolicy $workflow): bool
    {
        $prefix = $stage . '.';

        foreach ($this->_allTaskValues() as $taskName) {
            if (str_starts_with($taskName, $prefix) && $workflow->isTaskEnabled($taskName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Raise the public Next / complete hooks after persist succeeded and
     * payment did not fail or pause. Dispatch has not run yet.
     */
    private function _raiseSaveStageLifecycleEvents(WorkflowContext $context): void
    {
        if (!empty($context->taskState['save.spamDiscarded'])) {
            return;
        }

        if (!$context->processingSuccess) {
            return;
        }

        $request = $context->request;
        $submission = $request->submission;
        $form = $request->form;

        if ($context->isPageAdvance()) {
            $this->trigger(self::EVENT_AFTER_PAGE_ADVANCE, new SubmissionPageAdvanceEvent([
                'submission' => $submission,
                'form' => $form,
                'request' => $request,
                'context' => $context,
                'fromPage' => $form->getCurrentPage(),
                'toPage' => $context->nextPage,
            ]));

            return;
        }

        if (!$context->isCompletion() || !$submission->id) {
            return;
        }

        // Snapshot so we only persist listener status changes, not the fact
        // that persist just wrote a default status on a new submission.
        $statusIdBefore = $submission->statusId;

        $submission->trigger(Submission::EVENT_AFTER_COMPLETE, new SubmissionCompleteEvent([
            'submission' => $submission,
            'form' => $form,
            'request' => $request,
            'context' => $context,
            'fromPage' => $form->getCurrentPage(),
        ]));

        if ($submission->statusId !== $statusIdBefore) {
            Craft::$app->getElements()->saveElement($submission, false);
        }
    }

    private function _findPageById(Form $form, int $pageId): ?FieldLayoutPage
    {
        foreach ($form->getPages() as $page) {
            if ((int)$page->id === $pageId) {
                return $page;
            }
        }

        return null;
    }
}
