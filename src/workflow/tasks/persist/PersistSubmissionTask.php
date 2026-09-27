<?php
namespace verbb\formie\workflow\tasks\persist;

use verbb\formie\Formie;
use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

use Craft;

use RuntimeException;

class PersistSubmissionTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function execute(WorkflowContext $context): TaskResult
    {
        $submission = $context->command->submission;

        if ($context->taskState['persist.discardSpam'] ?? false) {
            Formie::$plugin->getSubmissions()->logSpam($submission);
            $context->processingSuccess = true;
            return TaskResult::continue();
        }

        $uploads = Formie::$plugin->getFileUploads();
        if (!$uploads->stageAccepted($submission)) {
            return TaskResult::stop($context->result(SubmissionOutcomeType::VALIDATION_FAILED));
        }
        return $uploads->withUploadLocks($submission, function () use ($uploads, $submission, $context): TaskResult {
            try {
                $bound = $uploads->bindAccepted($context->command);
            } catch (\yii\web\ForbiddenHttpException $e) {
                $submission->addError('form', $e->getMessage());
                return TaskResult::stop($context->result(SubmissionOutcomeType::VALIDATION_FAILED));
            }

            $completeAfterPromotion = !$submission->isIncomplete
                && ($context->becameComplete || !$submission->id)
                && $uploads->hasAcceptedUploads($submission);
            if ($completeAfterPromotion) {
                $submission->isIncomplete = true;
            }

            // Validate owns content validation; field persistence also enforces mandatory upload policy.
            try {
                if (!Craft::$app->getElements()->saveElement($submission, false)) {
                    $uploads->releaseUnpersistedBindings($bound);
                    if ($submission->hasErrors()) {
                        return TaskResult::stop($context->result(SubmissionOutcomeType::VALIDATION_FAILED));
                    }
                    throw new RuntimeException('Unable to persist the accepted submission.');
                }
            } catch (\Throwable $e) {
                $uploads->releaseUnpersistedBindings($bound);
                throw $e;
            }

            Formie::$plugin->getSubmissionOperations()->bindSubmission($context->command, (int)$submission->id);
            $uploads->bindPersisted($submission);
            $uploads->promoteAccepted($submission);

            // Filesystem work is complete. Commit completion and finalization together,
            // leaving the earlier incomplete save recoverable if this commit fails.
            $transaction = Craft::$app->getDb()->beginTransaction();
            try {
                if ($completeAfterPromotion) {
                    $submission->isIncomplete = false;
                    if (!Craft::$app->getElements()->saveElement($submission, false)) {
                        throw new RuntimeException('Unable to persist upload-backed completion.');
                    }
                }
                if (!$submission->isIncomplete) {
                    $uploads->finalizeSubmissionUploads((int)$submission->id);
                }
                $transaction->commit();
            } catch (\Throwable $e) {
                $transaction->rollBack();
                if ($completeAfterPromotion) {
                    $submission->isIncomplete = true;
                }
                throw $e;
            }

            $uploads->releaseRemoved($submission);
            $context->processingSuccess = true;
            $context->taskState['save.success'] = true;

            return TaskResult::continue();
        });
    }
}
