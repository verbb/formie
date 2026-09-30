<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\models\SubmissionCommand;

use Craft;

use RuntimeException;

/** Shared atomic content and upload persistence, independent of submission dispatch. */
final class SubmissionPersistence
{
    // Public Methods
    // =========================================================================

    public function persist(SubmissionCommand $command, bool $becameComplete = false, ?callable $afterSave = null): bool
    {
        $submission = $command->submission;
        $uploads = Formie::$plugin->getFileUploads();
        if (!$uploads->stageAccepted($command)) {
            return false;
        }
        return $uploads->withUploadLocks($submission, function () use ($uploads, $submission, $command, $becameComplete, $afterSave): bool {
            try {
                $bound = $uploads->bindAccepted($command);
            } catch (\yii\web\ForbiddenHttpException $e) {
                $submission->addError('form', $e->getMessage());
                return false;
            }

            $completeAfterPromotion = !$submission->isIncomplete
                && ($becameComplete || !$submission->id)
                && $uploads->hasAcceptedUploads($submission);
            if ($completeAfterPromotion) {
                $submission->isIncomplete = true;
            }

            // Validate owns content validation; field persistence also enforces mandatory upload policy.
            $saveTransaction = Craft::$app->getDb()->beginTransaction();
            try {
                if (!Craft::$app->getElements()->saveElement($submission, false)) {
                    $saveTransaction->rollBack();
                    $uploads->releaseUnpersistedBindings($bound);
                    if ($submission->hasErrors()) {
                        return false;
                    }
                    throw new RuntimeException('Unable to persist the accepted submission.');
                }
                if ($afterSave) {
                    $afterSave();
                }
                $saveTransaction->commit();
            } catch (\Throwable $e) {
                $saveTransaction->rollBack();
                $uploads->releaseUnpersistedBindings($bound);
                throw $e;
            }

            Formie::$plugin->getSubmissionOperations()->bindSubmission($command, (int)$submission->id);
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
                    if ($afterSave) {
                        $afterSave();
                    }
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
            return true;
        });
    }
}
