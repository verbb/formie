<?php
namespace verbb\formie\services;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\helpers\IntegrationTriggerEvents;
use verbb\formie\helpers\Table;
use verbb\formie\models\IntegrationDispatchContext;
use verbb\formie\models\IntegrationDispatchPlan;
use verbb\formie\models\Notification;
use verbb\formie\services\SubmissionWorkflow;

use Craft;
use craft\helpers\Json;

use yii\base\Component;

class IntegrationDispatch extends Component
{
    // Constants
    // =========================================================================

    public const PHASE_BEFORE = 'before';
    public const PHASE_AFTER = 'after';


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
        string $processMode = SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        array $triggerContext = [],
    ): void {
        $form = $submission->getForm();

        if (!$form || !$this->shouldOrchestrate($form)) {
            return;
        }

        $plan = $this->getPlan($form);
        $settings = Formie::$plugin->getSettings();
        $executor = Formie::$plugin->getIntegrationExecutor();
        $isSubmissionEdit = $processMode === SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING;
        $immediateHandles = $plan->getImmediateHandles($form);
        $queuedHandles = $plan->getQueuedHandles($form);

        if (!$triggerContext) {
            $triggerContext = [
                'processMode' => $processMode,
                'isSubmissionEdit' => $isSubmissionEdit,
                'triggerEvent' => IntegrationTriggerEvents::resolveFromProcessMode($processMode),
                'operatorInitiated' => false,
            ];
        }

        if ($immediateHandles) {
            $executor->runSteps($submission, $immediateHandles, $triggerContext, $plan);
        }

        if ($queuedHandles && $settings->useQueueForIntegrations) {
            $executor->queueSteps(
                $submission,
                $queuedHandles,
                $processMode,
                $triggerContext,
                $plan->notificationTiming === IntegrationDispatchPlan::NOTIFICATION_TIMING_AFTER,
            );

            return;
        }

        if ($queuedHandles) {
            $executor->runSteps($submission, $queuedHandles, $triggerContext, $plan);
        }

        if ($plan->notificationTiming === IntegrationDispatchPlan::NOTIFICATION_TIMING_AFTER) {
            $this->sendNotifications($submission, self::PHASE_AFTER);
        }
    }

    public function sendNotifications(Submission $submission, string $phase): void
    {
        $form = $submission->getForm();

        if (!$form) {
            return;
        }

        foreach ($form->getEnabledNotifications() as $notification) {
            if (!$this->shouldSendNotificationAtPhase($notification, $form, $phase)) {
                continue;
            }

            Formie::$plugin->getNotifications()->sendNotification($notification, $submission);
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

        if ($effectiveTiming === IntegrationDispatchPlan::NOTIFICATION_TIMING_AFTER) {
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
                ['integrationDispatchContext' => Json::encode($submission->integrationDispatchContext)],
                ['id' => $submission->id],
            )
            ->execute();
    }
}
