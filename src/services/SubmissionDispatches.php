<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\enums\NavigationIntent;
use verbb\formie\enums\SubmissionAuthorityType;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\enums\SubmissionPolicy;
use verbb\formie\helpers\Table;
use verbb\formie\models\IntegrationExecutionContext;
use verbb\formie\models\SubmissionAuthority;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\models\SubmissionDispatch;
use verbb\formie\workflow\WorkflowContext;

use Craft;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use yii\base\Component;

use RuntimeException;

class SubmissionDispatches extends Component
{
    // Constants
    // =========================================================================

    public const TABLE = '{{%formie_submission_dispatches}}';


    // Public Methods
    // =========================================================================

    public function recordIntent(WorkflowContext $context): ?string
    {
        $command = $context->command;
        $submission = $command->submission;

        if ($submission->isIncomplete || $command->operation === SubmissionOperation::SAVE_DRAFT || $command->policy === SubmissionPolicy::ADMINISTRATIVE_CREATE) {
            return null;
        }

        if (!Craft::$app->getDb()->getTransaction()?->isActive) {
            throw new RuntimeException('Dispatch intent must share the submission transaction.');
        }

        $kind = $command->operation === SubmissionOperation::REVISE
            ? ($submission->hasSpamChanged(true, false) ? 'unmark-spam' : 'edit') : 'completion';
        $key = $kind === 'completion' ? $kind : $kind . ':' . $command->expectedVersion;
        $identity = hash('sha256', Json::encode([(int)$submission->id, $key]));
        $now = gmdate('Y-m-d H:i:s');
        $newUid = StringHelper::UUID();
        Craft::$app->getDb()->createCommand()->upsert(self::TABLE, [
            'submissionId' => $submission->id, 'uid' => $newUid, 'identity' => $identity,
            'kind' => $kind, 'status' => 'ready', 'submissionVersion' => $submission->stateVersion,
            'command' => Json::encode([
                'operation' => $command->operation->value, 'authority' => $command->authority->type->value,
                'authorityScope' => $command->authority->scope, 'actorId' => $command->authority->actorId,
                'siteId' => $submission->siteId, 'statusChanged' => $submission->hasStatusChanged(),
                'spamUnmarked' => $submission->hasSpamChanged(true, false),
                'sendNotificationsOnSpamUnmark' => $command->sendNotificationsOnSpamUnmark,
                'triggerIntegrationsOnSpamUnmark' => $command->triggerIntegrationsOnSpamUnmark,
            ]),
            'dateCreated' => $now, 'dateUpdated' => $now,
        ], false)->execute();
        $uid = (new Query())->select('uid')->from(self::TABLE)->where(['identity' => $identity])->scalar();

        if (!$uid) {
            throw new RuntimeException('Unable to persist submission dispatch intent.');
        }

        $context->taskState['dispatch.uid'] = $uid;

        if (!isset($context->taskState['dispatch.lock']) && !$this->get((int)$submission->id, $uid)->schedulingComplete) {
            $lock = $this->_lock((int)$submission->id, $uid);

            if (!Craft::$app->getMutex()->acquire($lock, 10)) {
                throw new RuntimeException('Submission dispatch is already running.');
            }

            // Hold the claim through the post-commit completion listeners.
            $context->taskState['dispatch.lock'] = $lock;
        }

        return $uid;
    }

    public function get(int $submissionId, string $uid): ?SubmissionDispatch
    {
        $row = Craft::$app->getDb()->useMaster(fn() => (new Query())->from(self::TABLE)->where(['submissionId' => $submissionId, 'uid' => $uid])->one());

        if (!$row) {
            return null;
        }

        return new SubmissionDispatch([
            'submissionId' => (int)$row['submissionId'], 'uid' => $row['uid'], 'kind' => $row['kind'],
            'status' => $row['status'], 'submissionVersion' => (int)$row['submissionVersion'],
            'schedulingComplete' => (bool)$row['schedulingComplete'], 'failureCode' => $row['failureCode'],
            'command' => $row['command'] ? Json::decode($row['command']) : [],
        ]);
    }

    /** Direct/manual delivery still has a run, even without a completion workflow. */
    public function ensureRun(IntegrationExecutionContext $context): void
    {
        $now = gmdate('Y-m-d H:i:s');
        $version = (new Query())->select('stateVersion')->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $context->submissionId])->scalar();
        Craft::$app->getDb()->createCommand()->upsert(self::TABLE, [
            'submissionId' => $context->submissionId, 'uid' => $context->executionUid,
            'identity' => hash('sha256', Json::encode([$context->submissionId, 'manual:' . $context->executionUid])),
            'kind' => 'manual', 'status' => 'scheduled', 'submissionVersion' => (int)$version,
            'schedulingComplete' => true, 'dateCreated' => $now, 'dateUpdated' => $now,
        ], false)->execute();
    }

    /** Holds the run claim until the workflow's finally block releases it. */
    public function begin(WorkflowContext $context): bool
    {
        $uid = $context->taskState['dispatch.uid'] ?? null;
        $submission = $context->command->submission;

        if (!$uid) {
            return false;
        }

        $lock = $this->_lock((int)$submission->id, $uid);

        if (($context->taskState['dispatch.lock'] ?? null) !== $lock && !Craft::$app->getMutex()->acquire($lock, 10)) {
            throw new RuntimeException('Submission dispatch is already running.');
        }

        $run = $this->get((int)$submission->id, $uid);

        if (!$run || $run->schedulingComplete || $run->failureCode !== null) {
            unset($context->taskState['dispatch.lock']);
            Craft::$app->getMutex()->release($lock);

            return false;
        }

        $context->taskState['dispatch.lock'] = $lock;
        Db::update(self::TABLE, ['status' => 'running', 'startedAt' => gmdate('Y-m-d H:i:s')], ['submissionId' => $submission->id, 'uid' => $uid]);

        return true;
    }

    public function finish(WorkflowContext $context, bool $complete): void
    {
        if (!($lock = $context->taskState['dispatch.lock'] ?? null)) {
            return;
        }

        try {
            $where = ['submissionId' => $context->command->submission->id, 'uid' => $context->taskState['dispatch.uid']];
            Db::update(self::TABLE, ['schedulingComplete' => $complete, 'status' => $complete ? 'scheduled' : 'ready', 'scheduledAt' => null, 'dateUpdated' => gmdate('Y-m-d H:i:s')], $where + ['failureCode' => null]);
            $this->refresh((int)$where['submissionId'], $where['uid']);
        } finally {
            unset($context->taskState['dispatch.lock']);
            Craft::$app->getMutex()->release($lock);
        }
    }

    public function refresh(int $submissionId, string $uid): void
    {
        $lock = 'formie.dispatch-status.' . hash('sha256', $submissionId . ':' . $uid);

        if (!Craft::$app->getMutex()->acquire($lock, 10)) {
            throw new RuntimeException('Unable to update submission dispatch status.');
        }

        try {
            $this->_refresh($submissionId, $uid);
        } finally {
            Craft::$app->getMutex()->release($lock);
        }
    }

    /** @deprecated Only finishes `DispatchSubmission` jobs queued by an earlier release. */
    public function resume(int $submissionId, string $uid): void
    {
        // Match the processor's resource lock before claiming the dispatch itself.
        $lock = 'formie.resource.' . hash('sha256', 'submission:' . $submissionId);

        if (!Craft::$app->getMutex()->acquire($lock, 10)) {
            throw new RuntimeException('Submission is being updated. Try again shortly.');
        }

        $runLock = $this->_lock($submissionId, $uid);
        $claimed = false;

        try {
            $claimed = Craft::$app->getMutex()->acquire($runLock, 10);

            if (!$claimed) {
                throw new RuntimeException('Submission dispatch is already running.');
            }

            $run = $this->get($submissionId, $uid);

            if (!$run || $run->schedulingComplete || $run->failureCode !== null) {
                return;
            }

            $submission = Craft::$app->getDb()->useMaster(fn() => Submission::find()->id($submissionId)->siteId($run->command['siteId'])->status(null)->isIncomplete(null)->isSpam(null)->one());

            if (!$submission || $submission->isIncomplete) {
                return;
            }

            $form = $submission->getForm();
            $command = new SubmissionCommand(
                SubmissionOperation::from($run->command['operation']),
                NavigationIntent::STAY,
                new SubmissionAuthority(
                    SubmissionAuthorityType::from($run->command['authority']),
                    (int)$form->id,
                    $submissionId,
                    $run->command['authorityScope'] ?? 'dispatch:' . $uid,
                    isset($run->command['actorId']) ? (int)$run->command['actorId'] : null,
                ),
                $form,
                $submission,
                $submission->stateVersion,
                sendNotificationsOnSpamUnmark: $run->command['sendNotificationsOnSpamUnmark'],
                triggerIntegrationsOnSpamUnmark: $run->command['triggerIntegrationsOnSpamUnmark'],
            );
            Formie::$plugin->getSubmissionWorkflow()->resumeDispatch($command, $run, $runLock);
        } finally {
            if ($claimed && Craft::$app->getMutex()->isAcquired($runLock)) {
                Craft::$app->getMutex()->release($runLock);
            }

            Craft::$app->getMutex()->release($lock);
        }
    }


    // Private Methods
    // =========================================================================

    private function _refresh(int $submissionId, string $uid): void
    {
        $run = $this->get($submissionId, $uid);

        if (!$run || $run->failureCode !== null) {
            return;
        }

        // Provider writes belong to their integration, which may have handled a failed one and carried on.
        $statuses = Craft::$app->getDb()->useMaster(fn() => (new Query())->select('status')->distinct()->from(DeliveryAttempts::TABLE)->where(['submissionId' => $submissionId, 'executionUid' => $uid, 'parentUid' => null])->column());
        $status = match (true) {
            in_array('sending', $statuses, true) => 'running',
            !$run->schedulingComplete => $run->status === 'needs-attention' ? 'ready' : $run->status,
            in_array('pending', $statuses, true) => 'scheduled',
            (bool)array_intersect(['failed', 'rejected', 'unknown'], $statuses) => 'completed-with-failures',
            default => 'completed',
        };
        Db::update(self::TABLE, ['status' => $status, 'completedAt' => in_array($status, ['completed', 'completed-with-failures'], true) ? gmdate('Y-m-d H:i:s') : null], ['submissionId' => $submissionId, 'uid' => $uid]);
    }

    private function _lock(int $submissionId, string $uid): string
    {
        return 'formie.business-dispatch.' . hash('sha256', $submissionId . ':' . $uid);
    }
}
