<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\enums\SubmissionAuthorityType;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\errors\StateConflict;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\models\SubmissionErrors;
use verbb\formie\models\SubmissionOutcome;
use verbb\formie\workflow\WorkflowContext;

use craft\base\Element;

use yii\base\Component;
use yii\web\ForbiddenHttpException;

/** Executes resolved commands. Request parsing and response projection belong to SubmissionRequests. */
class SubmissionProcessor extends Component
{
    // Public Methods
    // =========================================================================

    public function executeCommand(SubmissionCommand $command, ?callable $populate = null): SubmissionOutcome
    {
        return Formie::$plugin->getSubmissionOperations()->execute($command, function () use ($command, $populate): SubmissionOutcome {
            if ($command->isInteractive() && $command->submission->id) {
                $purpose = $command->submission->isIncomplete ? SubmissionGrants::CONTINUE : SubmissionGrants::REVISE;
                if (!Formie::$plugin->getSubmissionGrants()->bound($command->form, $purpose, (int)$command->submission->id)) {
                    throw new ForbiddenHttpException('Submission is unavailable.');
                }
            }
            if ($command->isInteractive() && !$command->submission->id) {
                $current = Formie::$plugin->getSubmissionProgress()->getProgressState($command->form);
                if ($current?->submissionId && $command->form->settings->automaticSubmissionState) {
                    throw new StateConflict($current->version);
                }
            }
            if ($populate) {
                $populate();
            }
            if ($command->authority->type === SubmissionAuthorityType::GRAPHQL_ADMIN) {
                return $this->_persistAdministrative($command);
            }
            // Replay settles the accepted record. Reapplying values here would undo
            // conditional clearing and reevaluate Hidden sources in the callback's context.
            if ($command->operation !== SubmissionOperation::PAYMENT_REPLAY) {
                (new RuntimeConfiguration())->applyValues($command->submission);
            }
            return Formie::$plugin->getSubmissionWorkflow()->process($command);
        });
    }


    // Private Methods
    // =========================================================================

    private function _persistAdministrative(SubmissionCommand $command): SubmissionOutcome
    {
        $submission = $command->submission;
        $wasNew = !$submission->id;
        $wasIncomplete = $submission->isIncomplete;
        $context = new WorkflowContext($command);
        WorkflowContext::push($context);
        try {
            if (!$submission->title) {
                $submission->title = $command->form->getDefaultSubmissionTitle($submission);
            }
            $submission->setScenario(Element::SCENARIO_LIVE);
            $submission->validateCurrentPageOnly = false;
            if (!$submission->validate() || !(new SubmissionPersistence())->persist($command)) {
                return new SubmissionOutcome(SubmissionOutcomeType::VALIDATION_FAILED, errors: SubmissionErrors::fromSubmission($submission)->toValuePathMap());
            }
        } finally {
            WorkflowContext::pop();
        }
        // Preserve the element's completion event without entering the visitor
        // workflow, computing redirects, or scheduling a dispatch run.
        if (!$submission->isIncomplete && ($wasNew || $wasIncomplete)) {
            $submission->trigger(\verbb\formie\elements\Submission::EVENT_AFTER_COMPLETE, new \verbb\formie\events\SubmissionCompleteEvent(['submission' => $submission, 'form' => $command->form]));
        }
        return new SubmissionOutcome($wasNew ? SubmissionOutcomeType::COMPLETED : SubmissionOutcomeType::REVISED, (int)$submission->id, $submission->uid, $submission->stateVersion);
    }
}
