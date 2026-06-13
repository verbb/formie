<?php
namespace verbb\formie\services;

use verbb\formie\base\Element;
use verbb\formie\base\Integration;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\jobs\TriggerIntegration;
use verbb\formie\models\IntegrationDispatchContext;
use verbb\formie\models\IntegrationDispatchPlan;
use verbb\formie\models\IntegrationResponse;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Queue;

use yii\base\Component;

class IntegrationExecutor extends Component
{
    // Public Methods
    // =========================================================================

    public function resolveLegacyHandles(Form $form): array
    {
        $handles = [];

        foreach (Formie::$plugin->getIntegrations()->getAllEnabledIntegrationsForForm($form) as $integration) {
            if ($integration->supportsPayloadSending()) {
                $handles[] = (string)$integration->handle;
            }
        }

        return $handles;
    }

    public function runSteps(
        Submission $submission,
        array $handles,
        array $triggerContext,
        ?IntegrationDispatchPlan $plan = null,
    ): void {
        $form = $submission->getForm();

        if (!$form || !$handles) {
            return;
        }

        $integrationsByHandle = $this->_indexIntegrationsByHandle($form);
        $context = $plan?->shouldOrchestrate()
            ? Formie::$plugin->getIntegrationDispatch()->loadContext($submission)
            : null;

        foreach ($handles as $handle) {
            $integration = $integrationsByHandle[$handle] ?? null;

            if (!$integration || !$integration->supportsPayloadSending()) {
                continue;
            }

            if (!$integration->shouldTrigger($submission, $triggerContext)) {
                continue;
            }

            $integration->populateContext();

            $response = Formie::$plugin->getIntegrations()->sendIntegrationPayload($integration, $submission);
            $success = $this->_integrationResponseSucceeded($response);

            if ($context) {
                $this->_recordIntegrationResult($integration, $submission, $context, $success, $response);
            }

            if (!$success && $plan?->shouldStopOnFailure()) {
                break;
            }
        }

        if ($context) {
            Formie::$plugin->getIntegrationDispatch()->saveContext($submission, $context);
        }
    }

    public function queueSteps(
        Submission $submission,
        array $handles,
        string $processMode,
        array $triggerContext,
        bool $runAfterNotifications = false,
    ): void {
        $form = $submission->getForm();

        if (!$form || !$handles || !$submission->id) {
            return;
        }

        $settings = Formie::$plugin->getSettings();

        Queue::push(new TriggerIntegration([
            'submissionId' => $submission->id,
            'stepHandles' => array_values($handles),
            'processMode' => $processMode,
            'triggerEvent' => $triggerContext['triggerEvent'] ?? null,
            'operatorInitiated' => (bool)($triggerContext['operatorInitiated'] ?? false),
            'runAfterNotifications' => $runAfterNotifications,
            'formId' => $form->id ?? null,
            'formHandle' => $form->handle ?? null,
            'formTitle' => $form->title ?? null,
        ]), $settings->queuePriority);
    }

    public function runQueuedJob(
        Submission $submission,
        array $handles,
        string $processMode,
        array $triggerContext,
        bool $runAfterNotifications = false,
    ): void {
        $form = $submission->getForm();
        $plan = null;

        if ($form && Formie::$plugin->getIntegrationDispatch()->shouldOrchestrate($form)) {
            $plan = Formie::$plugin->getIntegrationDispatch()->getPlan($form);
        }

        $this->runSteps($submission, $handles, $triggerContext, $plan);

        if ($runAfterNotifications && $form) {
            Formie::$plugin->getIntegrationDispatch()->sendNotifications(
                $submission,
                IntegrationDispatch::PHASE_AFTER,
            );
        }
    }


    // Private Methods
    // =========================================================================

    private function _indexIntegrationsByHandle(Form $form): array
    {
        $integrationsByHandle = [];

        foreach (Formie::$plugin->getIntegrations()->getAllEnabledIntegrationsForForm($form) as $integration) {
            $integrationsByHandle[$integration->handle] = $integration;
        }

        return $integrationsByHandle;
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
