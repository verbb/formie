<?php
namespace verbb\formie\workflow\tasks\dispatch;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Query;
use craft\helpers\Db;

use DateTime;
use RuntimeException;
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
        public Submission $submission,
        public SubmissionOperation $operation,
        bool $initialSuccess,
        private ?string $idempotencyKey = null,
    ) {
        $this->traceId = sprintf('%s-%s', $submission->id ?: 'new', StringHelper::randomString(8));
        $this->success = $initialSuccess;
        $this->idempotencyKey = $this->_resolveIdempotencyKey($this->idempotencyKey ?? null);
    }

    public function isDispatchable(): bool
    {
        return !$this->submission->isIncomplete
            && in_array($this->operation, [SubmissionOperation::SUBMIT, SubmissionOperation::REVISE, SubmissionOperation::PAYMENT_REPLAY], true);
    }

    public function isSubmissionEditDispatch(): bool
    {
        return $this->operation === SubmissionOperation::REVISE;
    }

    public function applySpamFailureIfNeeded(): void
    {
        if ($this->submission->isSpam) {
            $this->success = false;
        }
    }

    public function hasMarker(string $stage): bool
    {
        $submission = $this->submission;

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

    /**
     * Serialize a logical side effect and record completion only after success.
     * Failed callbacks remain retryable. Providers should also receive idempotency
     * keys: a process crash after external success cannot be resolved by a DB marker.
     */
    public function runOnce(string $stage, callable $callback): bool
    {
        if (!$this->submission->id) {
            return $callback() !== false;
        }

        $key = 'formie.dispatch.' . hash('sha256', $this->submission->id . '|' . $stage . '|' . ($this->idempotencyKey ?? ''));
        $mutex = Craft::$app->getMutex();

        if (!$mutex->acquire($key, 10)) {
            throw new RuntimeException('Submission delivery is already in progress. Retry later.');
        }

        try {
            if ($this->hasMarker($stage)) {
                return false;
            }

            if ($callback() === false) {
                return false;
            }

            $this->markMarker($stage);
            return true;
        } finally {
            $mutex->release($key);
        }
    }

    public function markMarker(string $stage): void
    {
        $submission = $this->submission;

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
            throw $e;
        }
    }

    public function shouldRunSpamNotifications(): bool
    {
        $settings = Formie::$plugin->getSettings();

        return !$this->success && $this->submission->isSpam && $settings->spamEmailNotifications;
    }

    public function isAlreadyFinalized(): bool
    {
        if ($this->isSubmissionEditDispatch()) {
            return false;
        }

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

            throw $e;
        }
    }

    private function _resolveIdempotencyKey(?string $idempotencyKey): ?string
    {
        if (is_string($idempotencyKey)) {
            $idempotencyKey = trim($idempotencyKey);

            if ($idempotencyKey !== '') {
                return hash('sha256', $idempotencyKey);
            }
        }

        return null;
    }
}
