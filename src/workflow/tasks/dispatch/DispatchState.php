<?php
namespace verbb\formie\workflow\tasks\dispatch;

use verbb\formie\Formie;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\SubmissionWorkflow;

use Craft;
use craft\db\Query;
use craft\helpers\Db;

use DateTime;
use Throwable;

class DispatchState
{
    // Constants
    // =========================================================================

    public const MARKER_FINALIZED = 'finalized';
    public const MARKER_PAYMENT = 'payment';
    public const MARKER_NOTIFICATIONS = 'notifications';
    public const MARKER_INTEGRATIONS = 'integrations';
    public const MARKER_SPAM_NOTIFICATIONS = 'spamNotifications';


    // Properties
    // =========================================================================

    public string $traceId;
    public bool $success;


    // Public Methods
    // =========================================================================

    public function __construct(
        public SubmissionRequest $request,
        bool $initialSuccess,
        private ?string $idempotencyKey = null,
    ) {
        $this->traceId = sprintf('%s-%s', $request->submission->id ?: 'new', StringHelper::randomString(8));
        $this->success = $initialSuccess;
        $this->idempotencyKey = $this->_resolveIdempotencyKey($this->idempotencyKey ?? $request->requestToken);
    }

    public function isDispatchable(): bool
    {
        $isFinalSubmitAction = $this->request->submitAction === SubmissionWorkflow::SUBMIT_ACTION_SUBMIT;

        return $isFinalSubmitAction && !$this->request->submission->isIncomplete;
    }

    public function applySpamFailureIfNeeded(): void
    {
        if ($this->request->submission->isSpam) {
            $this->success = false;
        }
    }

    public function hasMarker(string $stage): bool
    {
        $submission = $this->request->submission;

        if (!$submission->id) {
            return false;
        }

        if ($this->_hasSubmissionWorkflowStageMarker((int)$submission->id, self::MARKER_FINALIZED, $this->idempotencyKey)) {
            return true;
        }

        if ($stage !== self::MARKER_FINALIZED) {
            if ($this->idempotencyKey === null && $this->_hasSubmissionWorkflowStageMarker((int)$submission->id, self::MARKER_FINALIZED, null)) {
                return true;
            }
        }

        return $this->_hasSubmissionWorkflowStageMarker((int)$submission->id, $stage, $this->idempotencyKey);
    }

    public function markMarker(string $stage): void
    {
        $submission = $this->request->submission;

        if (!$submission->id || $this->hasMarker($stage)) {
            return;
        }

        try {
            $now = new DateTime();
            $dateNow = Db::prepareDateForDb($now);
            $payload = [
                'submissionId' => $submission->id,
                'stage' => $stage,
                'idempotencyKey' => $this->idempotencyKey,
                'isDispatched' => true,
                'dateDispatched' => $dateNow,
                'dateUpdated' => $dateNow,
                'meta' => null,
            ];

            Craft::$app->getDb()->createCommand()->upsert(
                Table::FORMIE_SUBMISSION_WORKFLOW,
                array_merge($payload, ['dateCreated' => $dateNow]),
                $payload
            )->execute();
        } catch (Throwable $e) {
            Formie::error('Unable to persist dispatch marker - {e}.', ['e' => $e->getMessage()]);
        }
    }

    public function shouldRunSpamNotifications(): bool
    {
        $settings = Formie::$plugin->getSettings();

        return !$this->success && $this->request->submission->isSpam && $settings->spamEmailNotifications;
    }

    public function isAlreadyFinalized(): bool
    {
        return $this->hasMarker(self::MARKER_FINALIZED);
    }


    // Private Methods
    // =========================================================================

    private function _hasSubmissionWorkflowStageMarker(int $submissionId, string $stage, ?string $idempotencyKey): bool
    {
        $query = (new Query())
            ->from(Table::FORMIE_SUBMISSION_WORKFLOW)
            ->where([
                'submissionId' => $submissionId,
                'stage' => $stage,
                'isDispatched' => true,
            ]);

        if ($idempotencyKey === null) {
            $query->andWhere(['idempotencyKey' => null]);
        } else {
            $query->andWhere(['idempotencyKey' => $idempotencyKey]);
        }

        try {
            return (bool)$query->exists();
        } catch (Throwable $e) {
            Formie::error('Unable to read dispatch marker - {e}.', ['e' => $e->getMessage()]);

            return false;
        }
    }

    private function _resolveIdempotencyKey(?string $idempotencyKey): ?string
    {
        if (is_string($idempotencyKey)) {
            $idempotencyKey = trim($idempotencyKey);

            if ($idempotencyKey !== '') {
                return $idempotencyKey;
            }
        }

        return null;
    }
}
