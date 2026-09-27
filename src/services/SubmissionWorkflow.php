<?php
namespace verbb\formie\services;

use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\events\RegisterStageTasksEvent;
use verbb\formie\events\SubmissionCompleteEvent;
use verbb\formie\events\SubmissionPageAdvanceEvent;
use verbb\formie\events\SubmissionWorkflowStageEvent;
use verbb\formie\events\SubmissionWorkflowTaskEvent;
use verbb\formie\fields\FileUpload;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\models\SubmissionOutcome;
use verbb\formie\workflow\tasks\dispatch\DispatchState;
use verbb\formie\workflow\tasks\TaskRegistry;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\WorkflowManifest;

use Craft;

use yii\base\Component;

use LogicException;

class SubmissionWorkflow extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_REGISTER_STAGE_TASKS = 'registerStageTasks';
    public const EVENT_BEFORE_STAGE = 'beforeStage';
    public const EVENT_AFTER_STAGE = 'afterStage';
    public const EVENT_BEFORE_TASK = 'beforeTask';
    public const EVENT_AFTER_TASK = 'afterTask';
    public const EVENT_AFTER_PAGE_ADVANCE = 'afterPageAdvance';


    // Public Methods
    // =========================================================================

    /** Only the processor's resolved and authorized command enters this workflow. */
    public function process(SubmissionCommand $command): SubmissionOutcome
    {
        $context = new WorkflowContext($command);
        WorkflowContext::push($context);

        try {
            foreach (WorkflowManifest::stages() as $stageName => $builtIns) {
                $stage = Stage::from($stageName);
                if ($context->outcome && $stage !== Stage::FINALIZE) {
                    continue;
                }

                $registry = new TaskRegistry($builtIns);
                $this->trigger(self::EVENT_REGISTER_STAGE_TASKS, new RegisterStageTasksEvent([
                    'stage' => $stage,
                    'registry' => $registry,
                ]));
                $tasks = array_values(array_filter($registry->all(), static fn($task) => WorkflowManifest::applies($task, $stage, $command)));

                if (!$tasks || ($stage === Stage::SCREEN && !$context->attemptedCompletion)) {
                    continue;
                }
                if ($stage === Stage::VALIDATE && $command->navigation === \verbb\formie\enums\NavigationIntent::TARGET && ($context->taskState['navigation.backward'] ?? false)) {
                    continue;
                }
                if ($stage === Stage::DISPATCH && !$this->_prepareDispatch($context)) {
                    continue;
                }

                $this->trigger(self::EVENT_BEFORE_STAGE, new SubmissionWorkflowStageEvent([
                    'context' => $context, 'command' => $command, 'stage' => $stageName,
                ]));
                $result = TaskResult::continue();

                foreach ($tasks as $task) {
                    if ($task->publicAnchor) {
                        $this->trigger(self::EVENT_BEFORE_TASK, new SubmissionWorkflowTaskEvent([
                            'context' => $context, 'command' => $command, 'stage' => $stageName, 'task' => $task->id,
                        ]));
                    }
                    $result = $task->handler->execute($context);
                    if ($task->publicAnchor) {
                        $this->trigger(self::EVENT_AFTER_TASK, new SubmissionWorkflowTaskEvent([
                            'context' => $context, 'command' => $command, 'stage' => $stageName, 'task' => $task->id, 'result' => $result,
                        ]));
                    }
                    if ($result->outcome) {
                        $context->outcome = $result->outcome;
                        break;
                    }
                }

                // Invalid input never reaches content spam or a one-time external CAPTCHA.
                if ($stage === Stage::VALIDATE && $command->submission->hasErrors()) {
                    $result = TaskResult::stop($context->result(SubmissionOutcomeType::VALIDATION_FAILED));
                    $context->outcome = $result->outcome;
                }

                $this->trigger(self::EVENT_AFTER_STAGE, new SubmissionWorkflowStageEvent([
                    'context' => $context, 'command' => $command, 'stage' => $stageName, 'result' => $result,
                ]));
                if ($stage === Stage::VALIDATE && $command->submission->hasErrors()) {
                    $context->outcome = $context->result(SubmissionOutcomeType::VALIDATION_FAILED);
                }
                $context->stages[] = $stageName;

                if ($stage === Stage::PERSIST && !$context->outcome) {
                    $this->_raisePersistenceEvents($context);
                }
                if ($stage === Stage::DISPATCH) {
                    $state = $context->taskState['dispatch.state'];
                    if (($state->hasMarker(DispatchState::MARKER_NOTIFICATIONS) && $state->hasMarker(DispatchState::MARKER_INTEGRATIONS))
                        || $state->hasMarker(DispatchState::MARKER_SPAM_NOTIFICATIONS)) {
                        $state->markMarker(DispatchState::MARKER_FINALIZED);
                    }
                }
                if ($context->outcome && !in_array($context->outcome->type, [
                    SubmissionOutcomeType::PAYMENT_ACTION_REQUIRED,
                    SubmissionOutcomeType::PAYMENT_PENDING,
                    SubmissionOutcomeType::PAYMENT_FAILED,
                ], true)) {
                    return $context->outcome;
                }
            }

            return $context->outcome ?? throw new LogicException('The submission workflow did not produce an outcome.');
        } finally {
            FileUpload::clearStagedUploads($command->submission);
            WorkflowContext::pop();
        }
    }


    // Private Methods
    // =========================================================================

    private function _prepareDispatch(WorkflowContext $context): bool
    {
        $state = new DispatchState($context->command->submission, $context->command->operation, $context->processingSuccess, $context->command->operationId);
        if (!$state->isDispatchable() || $state->isAlreadyFinalized()) {
            return false;
        }
        $state->applySpamFailureIfNeeded();
        $context->taskState['dispatch.state'] = $state;
        return true;
    }

    private function _raisePersistenceEvents(WorkflowContext $context): void
    {
        if ($context->taskState['persist.discardSpam'] ?? false) {
            return;
        }
        $command = $context->command;
        $submission = $command->submission;
        $form = $command->form;

        if ($context->isPageAdvance()) {
            $this->trigger(self::EVENT_AFTER_PAGE_ADVANCE, new SubmissionPageAdvanceEvent([
                'submission' => $submission, 'form' => $form, 'command' => $command, 'context' => $context,
                'fromPage' => $form->getCurrentPage(), 'toPage' => $context->nextPage,
            ]));
        } elseif ($context->isCompletion() && $submission->id) {
            $statusBefore = $submission->statusId;
            $submission->trigger(Submission::EVENT_AFTER_COMPLETE, new SubmissionCompleteEvent([
                'submission' => $submission, 'form' => $form, 'command' => $command, 'context' => $context,
                'fromPage' => $form->getCurrentPage(),
            ]));
            if ($statusBefore !== $submission->statusId && !Craft::$app->getElements()->saveElement($submission, false)) {
                throw new LogicException('Unable to persist the completion event status.');
            }
        }
    }
}
