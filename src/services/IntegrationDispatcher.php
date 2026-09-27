<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\events\IntegrationDeliveryEvent;
use verbb\formie\helpers\DeliveryAttempt;
use verbb\formie\helpers\IntegrationTriggerEvents;
use verbb\formie\models\IntegrationBatchResult;
use verbb\formie\models\IntegrationRunContext;
use verbb\formie\models\IntegrationDispatchPlan;
use verbb\formie\models\IntegrationExecutionContext;
use verbb\formie\models\IntegrationResult;
use verbb\formie\models\Notification;

use Craft;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use yii\base\Component;

use DateTime;
use InvalidArgumentException;
use RuntimeException;

class IntegrationDispatcher extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_FINALIZED = 'finalized';
    public const PHASE_BEFORE = 'before';
    public const PHASE_AFTER = 'afterFinalizedDeliveryAttempts';
    public const PHASE_SYNCHRONOUS = 'afterSynchronousIntegrations';
    public const CONTEXT_TABLE = '{{%formie_integration_run_contexts}}';


    // Properties
    // =========================================================================

    private array $_runs = [];


    // Public Methods
    // =========================================================================

    public function getPlan(Form $form): IntegrationDispatchPlan
    {
        $settings = $form->settings->integrationDispatch ?? [];

        if (is_object($settings)) {
            $settings = (array)$settings;
        }

        return IntegrationDispatchPlan::fromFormSettings($settings);
    }

    public function shouldOrchestrate(Form $form): bool
    {
        return $this->getPlan($form)->shouldOrchestrate();
    }

    public function getOrchestratedIntegrationCount(Form $form): int
    {
        $plan = $this->getPlan($form);
        $handles = $plan->getOrderedHandles($form);

        return count($handles);
    }

    public function dispatchSubmission(
        Submission $submission,
        SubmissionOperation $operation = SubmissionOperation::SUBMIT,
        array $triggerContext = [],
    ): void {
        $form = $submission->getForm();

        if (!$form || !$this->shouldOrchestrate($form)) {
            return;
        }

        $plan = $this->getPlan($form);
        $settings = Formie::$plugin->getSettings();
        $executor = Formie::$plugin->getIntegrationRunner();
        $isSubmissionEdit = $operation === SubmissionOperation::REVISE;
        $synchronousHandles = $plan->getSynchronousHandles($form);
        $queuedHandles = $plan->getQueuedHandles($form);
        $needsAfterNotifications = $this->needsAfterNotificationsPhase($form);

        if (!$triggerContext) {
            $triggerContext = [
                'operation' => $operation,
                'isSubmissionEdit' => $isSubmissionEdit,
                'triggerEvent' => IntegrationTriggerEvents::resolveFromOperation($operation),
                'operatorInitiated' => false,
            ];
        }

        $executionKey = DeliveryAttempt::workflowIdentity() ?? StringHelper::UUID();
        $synchronousResult = null;

        if ($synchronousHandles) {
            $synchronousResult = $executor->runSteps($submission, $synchronousHandles, $triggerContext, $plan, $executionKey);

            if ($synchronousResult->stoppedOnFailure()) {
                $triggerContext['skipRemaining'] = true;
            }
        }

        $this->sendNotifications($submission, self::PHASE_SYNCHRONOUS, $executionKey);

        if ($queuedHandles && $settings->useQueueForIntegrations) {
            $executor->queueSteps(
                $submission,
                $queuedHandles,
                $operation,
                $triggerContext,
                $needsAfterNotifications,
                $executionKey,
            );

            return;
        }

        if ($queuedHandles) {
            $executor->runSteps($submission, $queuedHandles, $triggerContext + ['execution' => 'queued'], $plan, $executionKey);
        }

        $this->sendNotifications($submission, self::PHASE_AFTER, $executionKey);
    }

    /**
     * True when any enabled notification would send in the after-integrations phase,
     * including per-notification overrides when the form-wide default is before.
     */
    public function needsAfterNotificationsPhase(Form $form): bool
    {
        if (!$this->shouldOrchestrate($form)) {
            return false;
        }

        foreach ($form->getEnabledNotifications() as $notification) {
            if ($this->shouldSendNotificationAtPhase($notification, $form, self::PHASE_AFTER)) {
                return true;
            }
        }

        return false;
    }

    public function sendNotifications(Submission $submission, string $phase, ?string $deliveryKey = null): void
    {
        $form = $submission->getForm();

        if (!$form) {
            return;
        }

        if ($phase !== self::PHASE_BEFORE) {
            $plan = $this->getPlan($form);
            $context = $this->loadContext($submission, $deliveryKey);
            $handles = $phase === self::PHASE_SYNCHRONOUS ? $plan->getSynchronousHandles($form) : $plan->getOrderedHandles($form);
            foreach ($handles as $handle) {
                $result = $context->getResult($handle);
                if (!$result || !in_array($result['status'] ?? '', $plan->acceptedStatuses(), true) || ($deliveryKey !== null && ($result['executionUid'] ?? '') !== $deliveryKey)) {
                    return;
                }
            }
        }

        if ($phase === self::PHASE_AFTER && $deliveryKey !== null) {
            $batch = new IntegrationBatchResult();
            foreach ($handles as $handle) {
                $batch->record($handle, IntegrationResult::fromStorage($context->getResult($handle)));
            }
            $executionContext = new IntegrationExecutionContext((int)$submission->id, (int)$submission->formId, '@finalized', $deliveryKey);
            $attempts = Formie::$plugin->getDeliveryAttempts();
            $uid = $attempts->prepare($executionContext, 'finalized');
            $attempts->execute($uid, function () use ($executionContext, $batch, $uid) {
                $this->trigger(self::EVENT_FINALIZED, new IntegrationDeliveryEvent(['context' => $executionContext, 'attemptUid' => $uid, 'batch' => $batch]));
                return IntegrationResult::succeeded();
            });
        }
        foreach ($form->getEnabledNotifications() as $notification) {
            if (!$this->shouldSendNotificationAtPhase($notification, $form, $phase)) {
                continue;
            }

            Formie::$plugin->getNotifications()->sendNotification($notification, $submission, null, $deliveryKey);
        }
    }

    public function shouldSendNotificationAtPhase(Notification $notification, Form $form, string $phase): bool
    {
        $plan = $this->getPlan($form);

        if (!$plan->shouldOrchestrate()) {
            return $phase === self::PHASE_BEFORE;
        }

        $notificationTiming = (string)($notification->dispatchTiming ?? Notification::DISPATCH_TIMING_DEFAULT);
        $effectiveTiming = $notificationTiming;

        if ($notificationTiming === Notification::DISPATCH_TIMING_DEFAULT) {
            $effectiveTiming = $plan->notificationTiming;
        }

        if ($effectiveTiming === IntegrationDispatchPlan::NOTIFICATION_TIMING_SYNCHRONOUS) {
            return $phase === self::PHASE_SYNCHRONOUS;
        }

        if ($effectiveTiming === IntegrationDispatchPlan::NOTIFICATION_TIMING_AFTER || $effectiveTiming === 'afterIntegrations') {
            return $phase === self::PHASE_AFTER;
        }

        return $phase === self::PHASE_BEFORE;
    }

    public function withRun(Submission $submission, string $runUid, callable $callback): mixed
    {
        if ($runUid === '' || strlen($runUid) > 255) {
            throw new InvalidArgumentException('An integration run identity is required.');
        }
        $this->_runs[] = [(int)$submission->id, $runUid];
        try {
            return $callback();
        } finally {
            array_pop($this->_runs);
        }
    }

    public function loadContext(Submission $submission, ?string $runUid = null): IntegrationRunContext
    {
        $runUid ??= $this->currentRunUid($submission);
        if (!$submission->id || $runUid === null) {
            return new IntegrationRunContext();
        }
        $value = (new Query())->select('context')->from(self::CONTEXT_TABLE)
            ->where(['submissionId' => $submission->id, 'runUid' => $runUid])->scalar();
        return IntegrationRunContext::fromStorage($value);
    }

    public function saveContext(Submission $submission, IntegrationRunContext $context, string $runUid): void
    {
        if (!$submission->id || $runUid === '' || strlen($runUid) > 255) {
            throw new InvalidArgumentException('A saved submission and integration run identity are required.');
        }
        $mutex = Craft::$app->getMutex();
        $lock = 'formie.run-context.' . hash('sha256', $submission->id . ':' . $runUid);
        if (!$mutex->acquire($lock, 10)) {
            throw new RuntimeException('Unable to update integration run context.');
        }
        try {
            // Independent workers may finish different bindings in the same run.
            $stored = $this->loadContext($submission, $runUid);
            $stored->results = array_replace($stored->results, $context->results);
            $now = Db::prepareDateForDb(new DateTime());
            Craft::$app->getDb()->createCommand()->upsert(self::CONTEXT_TABLE, [
                'submissionId' => $submission->id, 'runUid' => $runUid,
                'context' => Json::encode($stored->toStorageArray()),
                'dateCreated' => $now, 'dateUpdated' => $now,
            ], ['context' => Json::encode($stored->toStorageArray()), 'dateUpdated' => $now])->execute();
        } finally {
            $mutex->release($lock);
        }
    }

    public function currentRunUid(Submission $submission): ?string
    {
        if ($this->_runs) {
            [$submissionId, $runUid] = $this->_runs[array_key_last($this->_runs)];
            return $submissionId === (int)$submission->id ? $runUid : null;
        }
        $workflow = \verbb\formie\workflow\WorkflowContext::current();
        return $workflow?->command->submission === $submission ? DeliveryAttempt::workflowIdentity() : null;
    }
}
