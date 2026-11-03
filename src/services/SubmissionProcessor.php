<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionAuthorityType;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\errors\StateConflict;
use verbb\formie\events\SubmissionCompleteEvent;
use verbb\formie\fields\FileUpload;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\models\SubmissionErrors;
use verbb\formie\models\SubmissionOutcome;
use verbb\formie\models\Settings;
use verbb\formie\workflow\WorkflowContext;

use Craft;
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

                // Replay settles accepted values; administrative GraphQL writes have
                // their own value contract and do not apply visitor defaults.
                if ($command->authority->type !== SubmissionAuthorityType::GRAPHQL_ADMIN && $command->operation !== SubmissionOperation::PAYMENT_REPLAY) {
                    (new RuntimeConfiguration())->applyValues($command->submission);
                }
            } finally {
                WorkflowContext::pop();
            }

            if ($command->authority->type === SubmissionAuthorityType::GRAPHQL_ADMIN) {
                return $this->_persistAdministrative($command);
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
        // Completing is a Submit, which is screened and delivered. Other saves only change content.
        $completing = $command->operation === SubmissionOperation::SUBMIT && !$submission->isIncomplete;
        $context = new WorkflowContext($command);
        $context->attemptedCompletion = $completing;
        $context->becameComplete = $completing;

        try {
            WorkflowContext::push($context);

            try {
                if (!$submission->title) {
                    $submission->title = $command->form->getDefaultSubmissionTitle($submission);
                }

                $submission->setScenario(Element::SCENARIO_LIVE);
                $submission->validateCurrentPageOnly = false;

                if (!$submission->validate()) {
                    return new SubmissionOutcome(SubmissionOutcomeType::VALIDATION_FAILED, errors: SubmissionErrors::fromSubmission($submission)->toValuePathMap());
                }

                if ($completing) {
                    $outcome = $this->_screenAdministrative($context);

                    if ($outcome) {
                        return $outcome;
                    }
                }

                $saved = (new SubmissionPersistence())->persist($command, $completing, function() use ($context, $submission, $completing): void {
                    Formie::$plugin->getQuestionnaireScoring()->scoreSubmission($submission);

                    if ($completing && ($submission->shouldSendNotifications() || $submission->shouldTriggerIntegrations())) {
                        Formie::$plugin->getSubmissionDispatches()->recordIntent($context);
                    }
                });

                if (!$saved) {
                    return new SubmissionOutcome(SubmissionOutcomeType::VALIDATION_FAILED, errors: SubmissionErrors::fromSubmission($submission)->toValuePathMap());
                }
            } finally {
                WorkflowContext::pop();
            }

            // Preserve the element's completion event without entering the visitor
            // workflow or computing redirects.
            if ($completing) {
                $submission->trigger(Submission::EVENT_AFTER_COMPLETE, new SubmissionCompleteEvent(['submission' => $submission, 'form' => $command->form]));
                $this->_dispatchAdministrative($context);
            }

            if ($completing && $submission->isSpam) {
                return $this->_rejectSpam($context);
            }

            return new SubmissionOutcome($wasNew || $completing ? SubmissionOutcomeType::COMPLETED : SubmissionOutcomeType::REVISED, (int)$submission->id, $submission->uid, $submission->stateVersion);
        } finally {
            Formie::$plugin->getSubmissionDispatches()->finish($context, false);
        }
    }

    private function _screenAdministrative(WorkflowContext $context): ?SubmissionOutcome
    {
        $submission = $context->command->submission;
        Formie::$plugin->getSubmissionWorkflow()->screen($context);

        if ($context->outcome) {
            return $context->outcome;
        }

        if ($submission->isSpam && !Formie::$plugin->getSettings()->shouldSaveSpam($submission)) {
            Formie::$plugin->getSubmissions()->logSpam($submission);
            FileUpload::clearStagedUploads($submission);

            return $this->_rejectSpam($context);
        }

        return null;
    }

    private function _dispatchAdministrative(WorkflowContext $context): void
    {
        $submission = $context->command->submission;
        $uid = $context->taskState['dispatch.uid'] ?? null;
        $lock = $context->taskState['dispatch.lock'] ?? null;

        if (!$uid || !$lock) {
            return;
        }

        $dispatches = Formie::$plugin->getSubmissionDispatches();
        $dispatches->updateAcceptedVersion($context);
        $run = $dispatches->get((int)$submission->id, $uid);

        // The workflow now owns the run claim, and releases it when it's finished.
        unset($context->taskState['dispatch.lock']);
        Formie::$plugin->getSubmissionWorkflow()->resumeDispatch($context->command, $run, $lock);
    }

    private function _rejectSpam(WorkflowContext $context): SubmissionOutcome
    {
        $settings = Formie::$plugin->getSettings();
        $fakeSuccess = $settings->spamBehaviour === Settings::SPAM_BEHAVIOUR_SUCCESS;

        if (!$fakeSuccess) {
            $context->command->submission->addError('form', $settings->spamBehaviourMessage ?: Craft::t('formie', 'Your submission could not be accepted.'));
        }

        return $context->result(SubmissionOutcomeType::REJECTED, ['fakeSuccess' => $fakeSuccess]);
    }
}
