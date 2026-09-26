<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\events\IntegrationDeliveryEvent;
use verbb\formie\helpers\DeliveryAttempt;
use verbb\formie\helpers\IntegrationTriggerEvents;
use verbb\formie\helpers\Table;
use verbb\formie\models\IntegrationBatchResult;
use verbb\formie\models\IntegrationDispatchContext;
use verbb\formie\models\IntegrationDispatchPlan;
use verbb\formie\models\IntegrationExecutionContext;
use verbb\formie\models\IntegrationResult;
use verbb\formie\models\Notification;
use verbb\formie\services\SubmissionWorkflow;

use Craft;
use craft\helpers\StringHelper;

use yii\base\Component;

class IntegrationDispatcher extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_FINALIZED = 'finalized';
    public const PHASE_BEFORE = 'before';
    public const PHASE_AFTER = 'afterFinalizedDeliveryAttempts';
    public const PHASE_SYNCHRONOUS = 'afterSynchronousIntegrations';


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
            $context = $this->loadContext($submission);
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

    public function loadContext(Submission $submission): IntegrationDispatchContext
    {
        return IntegrationDispatchContext::fromSubmission($submission->integrationDispatchContext ?? null);
    }

    public function saveContext(Submission $submission, IntegrationDispatchContext $context): void
    {
        $submission->integrationDispatchContext = $context->toStorageArray();

        if (!$submission->id) {
            return;
        }

        if (!Craft::$app->getDb()->columnExists(Table::FORMIE_SUBMISSIONS, 'integrationDispatchContext')) {
            return;
        }

        Craft::$app->getDb()->createCommand()
            ->update(
                Table::FORMIE_SUBMISSIONS,
                ['integrationDispatchContext' => $submission->integrationDispatchContext],
                ['id' => $submission->id],
            )
            ->execute();
    }
}
