<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\errors\StateConflict;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\models\SubmissionOutcome;
use verbb\formie\workflow\WorkflowContext;

use yii\base\Component;
use yii\web\ForbiddenHttpException;

/** Executes resolved commands. Request parsing and response projection belong to SubmissionRequests. */
class SubmissionProcessor extends Component
{
    // Public Methods
    // =========================================================================

    public function executeCommand(SubmissionCommand $command, ?callable $populate = null): SubmissionOutcome
    {
        return Formie::$plugin->getSubmissionOperations()->execute($command, function() use ($command, $populate): SubmissionOutcome {
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

            // Request normalization needs the authorized command, including whether
            // this is an editor correcting captured values or a visitor submitting them.
            WorkflowContext::push(new WorkflowContext($command));

            try {
                if ($populate) {
                    $populate();
                }

                // Replay settles accepted values.
                if ($command->operation !== SubmissionOperation::PAYMENT_REPLAY) {
                    (new RuntimeConfiguration())->applyValues($command->submission);
                }
            } finally {
                WorkflowContext::pop();
            }

            return Formie::$plugin->getSubmissionWorkflow()->process($command);
        });
    }
}
