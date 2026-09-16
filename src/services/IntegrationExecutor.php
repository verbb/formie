<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\Element;
use verbb\formie\base\Integration;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\Table;
use verbb\formie\jobs\TriggerIntegration;
use verbb\formie\models\IntegrationDispatchContext;
use verbb\formie\models\IntegrationDispatchPlan;
use verbb\formie\models\IntegrationExecutionResult;
use verbb\formie\models\IntegrationResponse;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\workflow\tasks\dispatch\DispatchState;

use Craft;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\Queue;
use craft\helpers\StringHelper;

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
        ?string $executionKey = null,
    ): IntegrationExecutionResult {
        $executionKey ??= \verbb\formie\helpers\DeliveryAttempt::workflowIdentity()
            ?? ($submission->id && $submission->uid ? StringHelper::UUID() : null);
        $result = new IntegrationExecutionResult();
        $form = $submission->getForm();

        if (!$form || !$handles) {
            return $result;
        }

        $integrationsByHandle = $this->_indexIntegrationsByHandle($form);
        $context = $plan?->shouldOrchestrate()
            ? Formie::$plugin->getIntegrationDispatch()->loadContext($submission)
            : null;

        $delivery = $executionKey === null ? null : new DispatchState(new SubmissionRequest([
            'form' => $form,
            'submission' => $submission,
            'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            'requestToken' => 'integration-job:' . $executionKey,
        ]), true);

        foreach ($handles as $handle) {
            $integration = $integrationsByHandle[$handle] ?? null;

            if (!$integration || !$integration->supportsPayloadSending()) {
                continue;
            }

            if (!$integration->shouldTrigger($submission, $triggerContext)) {
                continue;
            }

            $success = true;
            $execute = function () use ($integration, $submission, $context, $result, $handle, &$success): bool {
                $integration->populateContext($submission);
                $response = Formie::$plugin->getIntegrations()->sendIntegrationPayload($integration, $submission);
                $success = $this->_integrationResponseSucceeded($response);
                $result->recordAttempt((string)$handle, $success);

                if ($context) {
                    $this->_recordIntegrationResult($integration, $submission, $context, $success, $response);
                    // Persist successful outputs before checkpointing this step so
                    // later retries can still reference earlier created elements.
                    Formie::$plugin->getIntegrationDispatch()->saveContext($submission, $context);
                }

                return $success;
            };

            if ($delivery) {
                $attempt = new \verbb\formie\helpers\DeliveryAttempt((int)$submission->id, 'integration:' . $handle, $executionKey);
                $delivery->runOnce('integration.' . substr(hash('sha256', (string)$handle), 0, 48),
                    fn() => $attempt->execute([], fn(string $key) => $execute()),
                );
            } else {
                $execute();
            }

            if (!$success && $plan?->shouldStopOnFailure()) {
                $result->markStoppedOnFailure();
                break;
            }
        }

        if ($context) {
            Formie::$plugin->getIntegrationDispatch()->saveContext($submission, $context);
        }

        return $result;
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

        $integrationContext = Formie::$plugin->getSubmissionMetadata()->buildIntegrationContext($submission);

        $identity = \verbb\formie\helpers\DeliveryAttempt::workflowIdentity() ?? StringHelper::UUID();
        $enqueue = function (string $executionUid) use ($submission, $handles, $processMode, $triggerContext, $runAfterNotifications, $form, $integrationContext, $settings): bool {
            Queue::push(new TriggerIntegration([
                'submissionId' => $submission->id,
                'stepHandles' => array_values($handles),
                'executionUid' => $executionUid,
                'processMode' => $processMode,
                'triggerEvent' => $triggerContext['triggerEvent'] ?? null,
                'operatorInitiated' => (bool)($triggerContext['operatorInitiated'] ?? false),
                'runAfterNotifications' => $runAfterNotifications,
                'formId' => $form->id ?? null,
                'formHandle' => $form->handle ?? null,
                'formTitle' => $form->title ?? null,
                'integrationContext' => $integrationContext,
            ]), $settings->queuePriority);
            return true;
        };
        (new \verbb\formie\helpers\DeliveryAttempt((int)$submission->id, 'integration-queue', $identity))->execute(
            ['handles' => array_values($handles), 'processMode' => $processMode, 'triggerContext' => $triggerContext, 'afterNotifications' => $runAfterNotifications],
            $enqueue,
            // A repeated enqueue uses the same guarded integration execution ID.
            PHP_INT_MAX,
        );
    }

    public function runQueuedJob(
        Submission $submission,
        array $handles,
        string $processMode,
        array $triggerContext,
        bool $runAfterNotifications = false,
        ?string $executionKey = null,
    ): IntegrationExecutionResult {
        // Serialize the batch as well as individual steps, including context and
        // after-notifications. A worker may have loaded the submission before waiting.
        $key = 'formie.integration-job.' . $submission->id;
        $mutex = Craft::$app->getMutex();

        if (!$mutex->acquire($key, 10)) {
            throw new \RuntimeException('Integration delivery is already in progress. Retry later.');
        }

        try {
            if ($submission->id) {
                $stored = (new Query())
                    ->select(['integrationDispatchContext'])
                    ->from(Table::FORMIE_SUBMISSIONS)
                    ->where(['id' => $submission->id])
                    ->scalar();
                $submission->integrationDispatchContext = IntegrationDispatchContext::fromSubmission(Json::decodeIfJson($stored))->toStorageArray();
            }

            return $this->_runQueuedJob($submission, $handles, $triggerContext, $runAfterNotifications, $executionKey);
        } finally {
            $mutex->release($key);
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

    private function _runQueuedJob(
        Submission $submission,
        array $handles,
        array $triggerContext,
        bool $runAfterNotifications,
        ?string $executionKey,
    ): IntegrationExecutionResult {
        $form = $submission->getForm();
        $plan = null;

        if ($form && Formie::$plugin->getIntegrationDispatch()->shouldOrchestrate($form)) {
            $plan = Formie::$plugin->getIntegrationDispatch()->getPlan($form);
        }

        $result = $this->runSteps($submission, $handles, $triggerContext, $plan, $executionKey);

        // Deliver the after phase only after the entire batch has succeeded.
        if ($runAfterNotifications && $form && $result->success) {
            $send = fn() => Formie::$plugin->getIntegrationDispatch()->sendNotifications($submission, IntegrationDispatch::PHASE_AFTER, $executionKey);

            if ($executionKey === null) {
                $send();
            } else {
                $delivery = new DispatchState(new SubmissionRequest([
                    'form' => $form,
                    'submission' => $submission,
                    'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
                    'requestToken' => 'integration-job:' . $executionKey,
                ]), true);
                $delivery->runOnce('afterNotifications', $send);
            }
        }

        return $result;
    }

}
