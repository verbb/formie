<?php
namespace verbb\formie\services;

use verbb\formie\base\Element;
use verbb\formie\base\Integration;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\jobs\TriggerSubmissionDispatch;
use verbb\formie\models\IntegrationDispatchContext;
use verbb\formie\models\IntegrationDispatchPlan;
use verbb\formie\models\IntegrationResponse;
use verbb\formie\models\Notification;
use verbb\formie\services\SubmissionWorkflow;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Json;
use craft\helpers\Queue;

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

    public function dispatchSubmission(Submission $submission, string $processMode = SubmissionWorkflow::PROCESS_MODE_SUBMIT): void
    {
        $form = $submission->getForm();

        if (!$form || !$this->shouldOrchestrate($form)) {
            return;
        }

        $plan = $this->getPlan($form);

        $settings = Formie::$plugin->getSettings();
        $isSubmissionEdit = $processMode === SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING;
        $immediateHandles = $plan->getImmediateHandles($form);
        $queuedHandles = $plan->getQueuedHandles($form);
        $context = $this->loadContext($submission);

        if ($immediateHandles) {
            $this->_runIntegrationHandles(
                $submission,
                $form,
                $plan,
                $immediateHandles,
                $context,
                $isSubmissionEdit,
            );
        }

        if ($queuedHandles && $settings->useQueueForIntegrations) {
            Queue::push(new TriggerSubmissionDispatch([
                'submissionId' => $submission->id,
                'processMode' => $processMode,
                'stepHandles' => $queuedHandles,
                'runAfterNotifications' => $plan->notificationTiming === IntegrationDispatchPlan::NOTIFICATION_TIMING_AFTER,
            ]), $settings->queuePriority);

            return;
        }

        if ($queuedHandles) {
            $this->_runIntegrationHandles(
                $submission,
                $form,
                $plan,
                $queuedHandles,
                $context,
                $isSubmissionEdit,
            );
        }

        if ($plan->notificationTiming === IntegrationDispatchPlan::NOTIFICATION_TIMING_AFTER) {
            $this->sendNotifications($submission, self::PHASE_AFTER);
        }
    }

    public function runQueuedSteps(Submission $submission, array $stepHandles, string $processMode, bool $runAfterNotifications): void
    {
        $form = $submission->getForm();

        if (!$form) {
            return;
        }

        $plan = $this->getPlan($form);
        $context = $this->loadContext($submission);
        $isSubmissionEdit = $processMode === SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING;

        $this->_runIntegrationHandles(
            $submission,
            $form,
            $plan,
            $stepHandles,
            $context,
            $isSubmissionEdit,
        );

        if ($runAfterNotifications) {
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


    // Private Methods
    // =========================================================================

    private function _runIntegrationHandles(
        Submission $submission,
        Form $form,
        IntegrationDispatchPlan $plan,
        array $handles,
        IntegrationDispatchContext $context,
        bool $isSubmissionEdit,
    ): void {
        $integrationsByHandle = [];

        foreach (Formie::$plugin->getIntegrations()->getAllEnabledIntegrationsForForm($form) as $integration) {
            $integrationsByHandle[$integration->handle] = $integration;
        }

        foreach ($handles as $handle) {
            $integration = $integrationsByHandle[$handle] ?? null;

            if (!$integration || !$integration->supportsPayloadSending()) {
                continue;
            }

            if ($isSubmissionEdit && !$integration->shouldTriggerOnSubmissionEdit()) {
                continue;
            }

            if (!$integration->shouldTrigger($submission, [
                'processMode' => $isSubmissionEdit ? SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING : SubmissionWorkflow::PROCESS_MODE_SUBMIT,
                'isSubmissionEdit' => $isSubmissionEdit,
            ])) {
                continue;
            }

            $integration->populateContext();

            $response = Formie::$plugin->getIntegrations()->sendIntegrationPayload($integration, $submission);
            $success = $this->_integrationResponseSucceeded($response);

            $this->_recordIntegrationResult($integration, $submission, $context, $success, $response);

            if (!$success && $plan->shouldStopOnFailure()) {
                break;
            }
        }

        $this->saveContext($submission, $context);
    }

    private function _integrationResponseSucceeded(mixed $response): bool
    {
        if ($response instanceof IntegrationResponse) {
            return (bool)$response->success;
        }

        return (bool)$response;
    }

    private function _recordIntegrationResult(
        Integration $integration,
        Submission $submission,
        IntegrationDispatchContext $context,
        bool $success,
        mixed $response,
    ): void {
        $result = [
            'success' => $success,
            'handle' => $integration->handle,
            'type' => get_class($integration),
        ];

        $element = $this->_resolveCreatedElement($integration, $response);

        if ($element) {
            $result['elementType'] = get_class($element);
            $result['elementId'] = (int)$element->id;
            $result['url'] = method_exists($element, 'getUrl') ? (string)$element->getUrl() : null;

            if ($element instanceof \craft\elements\User && !$submission->userId) {
                $submission->setUser($element);

                if ($submission->id) {
                    Craft::$app->getElements()->saveElement($submission, false);
                }
            }
        }

        $context->record($integration->handle, array_filter($result, fn($value) => $value !== null && $value !== ''));
    }

    private function _resolveCreatedElement(Integration $integration, mixed $response): ?ElementInterface
    {
        $dispatchElement = $integration->context['dispatchElement'] ?? null;

        if (is_array($dispatchElement) && !empty($dispatchElement['elementId'])) {
            $element = Craft::$app->getElements()->getElementById(
                (int)$dispatchElement['elementId'],
                $dispatchElement['elementType'] ?? null,
            );

            if ($element instanceof ElementInterface) {
                return $element;
            }
        }

        if ($integration instanceof Element) {
            $queueJob = $integration->getQueueJob();

            if ($queueJob && isset($queueJob->payload) && $queueJob->payload instanceof ElementInterface) {
                return $queueJob->payload;
            }
        }

        return null;
    }
}
