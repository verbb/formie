<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\enums\workflow\Task;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\WorkflowPolicy;
use verbb\formie\workflow\tasks\dispatch\SendNotificationsTask;
use verbb\formie\workflow\tasks\dispatch\TriggerIntegrationsTask;
use verbb\formie\workflow\tasks\screen\RunCaptchaChecksTask;
use verbb\formie\workflow\tasks\screen\RunSubmissionGuardsTask;
use verbb\formie\workflow\tasks\screen\RunSpamChecksTask;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

use yii\base\Component;
use yii\base\InvalidArgumentException;

class WorkflowTaskRunner extends Component
{
    // Public Methods
    // =========================================================================

    public function runTask(SubmissionRequest $request, Task|string $task): TaskResult
    {
        $task = $task instanceof Task ? $task : Task::from($task);
        $taskHandler = $this->_createTaskHandler($task);
        $context = new WorkflowContext($request, WorkflowPolicy::fromTasks($request, [$taskHandler->getName()]));
        $stageResult = Formie::$plugin->getSubmissionWorkflow()->runStageTasks($context, $taskHandler->getStage(), [$taskHandler]);

        return new TaskResult($stageResult->success, $stageResult->halt, $stageResult->meta);
    }

    public function runTaskForSubmission(Submission $submission, Task|string $task): TaskResult
    {
        $task = $task instanceof Task ? $task : Task::from($task);
        $this->_assertStandaloneAllowed($task);

        $form = $submission->getForm();

        if (!$form) {
            throw new InvalidArgumentException('Cannot run standalone task without a form on submission.');
        }

        $request = new SubmissionRequest([
            'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            'form' => $form,
            'submission' => $submission,
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
        ]);

        return $this->runTask($request, $task);
    }

    public function canRunStandalone(Task|string $task): bool
    {
        $task = $task instanceof Task ? $task : Task::from($task);

        return in_array($task, $this->_standaloneAllowedTasks(), true);
    }


    // Private Methods
    // =========================================================================

    private function _assertStandaloneAllowed(Task $task): void
    {
        if ($this->canRunStandalone($task)) {
            return;
        }

        throw new InvalidArgumentException(sprintf('Task "%s" does not support standalone execution.', $task->value));
    }

    private function _createTaskHandler(Task $task): TaskInterface
    {
        return match ($task) {
            Task::SCREEN_RUN_SUBMISSION_GUARDS => new RunSubmissionGuardsTask(),
            Task::SCREEN_RUN_CAPTCHA_CHECKS => new RunCaptchaChecksTask(),
            Task::SCREEN_RUN_SPAM_CHECKS => new RunSpamChecksTask(),
            Task::DISPATCH_SEND_NOTIFICATIONS => new SendNotificationsTask(),
            Task::DISPATCH_TRIGGER_INTEGRATIONS => new TriggerIntegrationsTask(),
            default => throw new InvalidArgumentException(sprintf('Task "%s" is not supported via WorkflowTaskRunner.', $task->value)),
        };
    }

    private function _standaloneAllowedTasks(): array
    {
        return [
            Task::SCREEN_RUN_SUBMISSION_GUARDS,
            Task::SCREEN_RUN_CAPTCHA_CHECKS,
            Task::SCREEN_RUN_SPAM_CHECKS,
            Task::DISPATCH_SEND_NOTIFICATIONS,
            Task::DISPATCH_TRIGGER_INTEGRATIONS,
        ];
    }
}
