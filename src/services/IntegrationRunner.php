<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\enums\IntegrationStatus;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\events\IntegrationDeliveryEvent;
use verbb\formie\helpers\DeliveryAttempt;
use verbb\formie\helpers\IntegrationTriggerEvents;
use verbb\formie\helpers\Table;
use verbb\formie\jobs\TriggerIntegration;
use verbb\formie\models\FormIntegration;
use verbb\formie\models\IntegrationBatchResult;
use verbb\formie\models\IntegrationDispatchPlan;
use verbb\formie\models\IntegrationExecutionContext;
use verbb\formie\models\IntegrationResult;

use Craft;
use craft\db\Query;
use craft\elements\User;
use craft\helpers\Json;
use craft\helpers\Queue;
use craft\helpers\StringHelper;

use yii\base\Component;

use RuntimeException;
use Throwable;

class IntegrationRunner extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_EVALUATED = 'evaluated';
    public const EVENT_SKIPPED = 'skipped';
    public const EVENT_RESULT = 'result';
    public const EVENT_BATCH_COMPLETED = 'batchCompleted';
    public const EVENT_QUEUED = 'queued';


    // Public Methods
    // =========================================================================

    public function resolveLegacyHandles(Form $form): array
    {
        return array_values(array_map(fn($integration) => $integration->handle, array_filter(
            Formie::$plugin->getIntegrations()->getAllEnabledIntegrationsForForm($form),
            fn($integration) => $integration->supportsPayloadSending(),
        )));
    }

    public function runSteps(Submission $submission, array $handles, array $triggerContext, ?IntegrationDispatchPlan $plan = null, ?string $executionKey = null): IntegrationBatchResult
    {
        $batch = new IntegrationBatchResult();
        $form = $submission->getForm();
        if (!$form) {
            return $batch;
        }
        $executionKey ??= DeliveryAttempt::workflowIdentity() ?? StringHelper::UUID();
        $available = [];
        foreach (Formie::$plugin->getIntegrations()->getAllEnabledIntegrationsForForm($form) as $integration) {
            $available[$integration->handle] = $integration;
        }
        $stopped = (bool)($triggerContext['skipRemaining'] ?? false);
        foreach ($handles as $handle) {
            $integration = $available[$handle] ?? null;
            if ($stopped || !$integration instanceof Integration || !$integration->supportsPayloadSending()) {
                $result = IntegrationResult::skipped($stopped ? 'previous_step_failed' : 'disabled_or_missing');
                $context = new IntegrationExecutionContext((int)$submission->id, (int)$submission->formId, $handle, $executionKey, $triggerContext['execution'] ?? 'synchronous');
                $attempts = Formie::$plugin->getDeliveryAttempts();
                $uid = $attempts->prepare($context, 'integration');
                $attempts->execute($uid, fn() => $result);
                $this->_saveProjection($handle, $submission, $result, $executionKey);
                $batch->record($handle, $result);
                continue;
            }
            if (isset($triggerContext['bindings'][$handle])) {
                $integration = FormIntegration::fromSettings($integration, $triggerContext['bindings'][$handle])->createRuntime($integration);
            }
            $execution = $triggerContext['execution'] ?? 'synchronous';
            $result = $this->runIntegration($integration, $submission, $executionKey, $execution, $triggerContext);
            $batch->record($handle, $result);
            if (!in_array($result->status, [IntegrationStatus::Succeeded, IntegrationStatus::Skipped], true) && $plan?->shouldStopOnFailure()) {
                $batch->markStoppedOnFailure();
                $stopped = true;
            }
        }
        $this->trigger(self::EVENT_BATCH_COMPLETED, new IntegrationDeliveryEvent(['context' => new IntegrationExecutionContext((int)$submission->id, (int)$submission->formId, '@dispatch', $executionKey, $triggerContext['execution'] ?? 'synchronous'), 'batch' => $batch]));
        return $batch;
    }

    public function runIntegration(Integration $connection, Submission $submission, string $executionKey, string $execution, array $triggerContext = []): IntegrationResult
    {
        $mutex = Craft::$app->getMutex();
        $lock = 'formie.binding.' . hash('sha256', $submission->id . ':' . $connection->handle);
        if (!$mutex->acquire($lock, 10)) {
            return IntegrationResult::unknown('binding_running');
        }
        try {
            return $this->_runIntegration($connection, $submission, $executionKey, $execution, $triggerContext);
        } finally {
            $mutex->release($lock);
        }
    }

    public function queueSteps(Submission $submission, array $handles, SubmissionOperation $operation, array $triggerContext, bool $runAfterNotifications = false, ?string $executionKey = null): void
    {
        if (!$submission->id || !$handles) {
            return;
        }
        $identity = $executionKey ?? DeliveryAttempt::workflowIdentity() ?? StringHelper::UUID();
        $context = new IntegrationExecutionContext((int)$submission->id, (int)$submission->formId, '@dispatch', $identity, 'queued');
        $bindings = [];
        foreach (Formie::$plugin->getIntegrations()->getAllEnabledIntegrationsForForm($submission->getForm()) as $integration) {
            $bindings[$integration->handle] = FormIntegration::filterSettings($integration, ['enabled' => $integration->getEnabled()] + $integration->getAttributes());
        }
        $triggerContext['bindings'] = $bindings;
        $uid = Formie::$plugin->getDeliveryAttempts()->prepare($context, 'dispatch', [
            'handles' => array_values($handles), 'operation' => $operation->value,
            'triggerContext' => $triggerContext, 'afterNotifications' => $runAfterNotifications,
        ]);
        (new DeliveryAttempt((int)$submission->id, 'integration-queue', $identity))->execute(['attemptUid' => $uid], function () use ($uid, $context): bool {
            Queue::push(new TriggerIntegration(['deliveryAttemptUid' => $uid]), Formie::$plugin->getSettings()->queuePriority);
            Formie::$plugin->getDeliveryAttempts()->checkpoint($uid, 'queued');
            $this->trigger(self::EVENT_QUEUED, new IntegrationDeliveryEvent(['context' => $context, 'attemptUid' => $uid]));
            return true;
        }, PHP_INT_MAX);
    }

    public function runQueuedAttempt(string $uid): IntegrationResult
    {
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $row = $attempts->get($uid);
        $data = $attempts->data($uid);
        $submission = Submission::find()->id($row['submissionId'])->status(null)->isIncomplete(null)->isSpam(null)->one();
        if (!$submission) {
            return $attempts->execute($uid, fn() => IntegrationResult::rejected('submission_missing'));
        }
        Craft::$app->language = $submission->getSite()->language;
        Craft::$app->set('locale', Craft::$app->getI18n()->getLocaleById($submission->getSite()->language));
        Craft::$app->getSites()->setCurrentSite($submission->getSite());
        if ($row['step'] === 'integration') {
            foreach (Formie::$plugin->getIntegrations()->getAllEnabledIntegrationsForForm($submission->getForm()) as $connection) {
                if ($connection->handle === $row['binding']) {
                    $integration = FormIntegration::fromSettings($connection, $data['settings'] ?? [])->createRuntime($connection);
                    $result = $this->runIntegration($integration, $submission, $row['executionUid'], $row['execution'], ['triggerEvent' => IntegrationTriggerEvents::SUBMIT, 'operatorInitiated' => true, 'retryAttemptUid' => $uid]);
                    $this->finalizeDelivery($uid, $result);
                    return $result;
                }
            }
            return $attempts->execute($uid, fn() => IntegrationResult::skipped('disabled_or_missing'));
        }
        return $attempts->execute($uid, function () use ($submission, $data, $row): IntegrationResult {
            $triggerContext = $data['triggerContext'];
            $triggerContext['execution'] = 'queued';
            $batch = $this->runSteps($submission, $data['handles'], $triggerContext, Formie::$plugin->getIntegrationDispatcher()->getPlan($submission->getForm()), $row['executionUid']);
            Formie::$plugin->getIntegrationDispatcher()->sendNotifications($submission, IntegrationDispatcher::PHASE_AFTER, $row['executionUid']);
            if ($batch->accepts()) {
                return IntegrationResult::succeeded();
            }
            foreach ($batch->results() as $item) {
                if ($item['result']->requiresReconciliation()) {
                    return $item['result'];
                }
            }
            return IntegrationResult::failed('batch_failed', true);
        });
    }

    public function finalizeDelivery(string $uid, IntegrationResult $result): void
    {
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $row = $attempts->get($uid);
        if (!in_array($row['step'], ['integration', 'dispatch'], true)) {
            return;
        }
        $submission = Submission::find()->id($row['submissionId'])->status(null)->isIncomplete(null)->isSpam(null)->one();
        if (!$submission) {
            return;
        }
        if ($row['step'] === 'integration') {
            $this->_saveProjection($row['binding'], $submission, $result, $row['executionUid']);
        }
        $dispatcher = Formie::$plugin->getIntegrationDispatcher();
        if ($row['execution'] === 'synchronous') {
            $dispatcher->sendNotifications($submission, IntegrationDispatcher::PHASE_SYNCHRONOUS, $row['executionUid']);
        }
        $dispatcher->sendNotifications($submission, IntegrationDispatcher::PHASE_AFTER, $row['executionUid']);
    }


    // Private Methods
    // =========================================================================

    private function _runIntegration(Integration $connection, Submission $submission, string $executionKey, string $execution, array $triggerContext): IntegrationResult
    {
        $integration = clone $connection;
        $integration->populateContext($submission);
        $conditionEvaluation = $integration->enableConditions ? \verbb\formie\helpers\ConditionsHelper::evaluate($integration->conditions ?? [], $submission, 'integration') : null;
        $invalidConditions = $conditionEvaluation && $conditionEvaluation->value === null;
        $eligible = $integration->shouldTrigger($submission, $triggerContext) && $integration->enforceOptInField($submission);
        $overrides = [];
        $reason = ($triggerContext['operatorInitiated'] ?? false) ? 'manual' : 'automatic';
        if ($triggerContext['force'] ?? false) {
            if (!Craft::$app->getUser()->checkPermission('formie-forceIntegrations') || !Formie::$plugin->getPermissions()->canSaveSubmissions(Craft::$app->getUser()->getIdentity(), $submission->getForm()) || trim((string)($triggerContext['reason'] ?? '')) === '') {
                throw new RuntimeException('Force execution requires scoped authority and an audit reason.');
            }
            $reason = $triggerContext['reason'];
            $overrides = ['conditions', 'optIn'];
        }
        if (isset($triggerContext['retryAttemptUid'])) {
            $original = Formie::$plugin->getDeliveryAttempts()->context($triggerContext['retryAttemptUid']);
            if ($original->submissionId !== (int)$submission->id || $original->binding !== $integration->handle || $original->executionUid !== $executionKey) {
                throw new RuntimeException('Retry authority does not match this delivery.');
            }
            $overrides = $original->overrides;
            $reason = $original->reason;
        }
        $context = new IntegrationExecutionContext((int)$submission->id, (int)$submission->formId, (string)$integration->handle, $executionKey, $execution, $reason, $eligible, $overrides);
        $this->trigger(self::EVENT_EVALUATED, new IntegrationDeliveryEvent(['context' => $context]));
        if ($invalidConditions || (!$eligible && !$overrides)) {
            $result = $invalidConditions ? new IntegrationResult(IntegrationStatus::Rejected, code: 'invalid_conditions', diagnostics: $conditionEvaluation->diagnostics) : IntegrationResult::skipped('conditions');
            $this->trigger($invalidConditions ? self::EVENT_RESULT : self::EVENT_SKIPPED, new IntegrationDeliveryEvent(['context' => $context, 'result' => $result]));
            $attempts = Formie::$plugin->getDeliveryAttempts();
            $uid = $attempts->prepare($context, 'integration');
            $attempts->execute($uid, fn() => $result);
            $this->_saveProjection($integration, $submission, $result, $executionKey);
            return $result;
        }
        $integration->setScenario(Integration::SCENARIO_FORM);
        if (!$integration->validate($integration->getFormSettingAttributes())) {
            $result = IntegrationResult::rejected('invalid_form_settings');
            $attempts = Formie::$plugin->getDeliveryAttempts();
            $uid = $attempts->prepare($context, 'integration');
            $attempts->execute($uid, fn() => $result);
            $this->_saveProjection($integration, $submission, $result, $executionKey);
            return $result;
        }
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $uid = $attempts->prepare($context, 'integration', ['settings' => array_intersect_key(['enabled' => $integration->getEnabled()] + $integration->getAttributes(), array_fill_keys($integration->getFormSettingAttributes(), true))]);
        $legacy = (new DeliveryAttempt((int)$submission->id, 'integration:' . $integration->handle, $executionKey))->getMetadata();
        if (!$attempts->hasReconciliation($uid) && in_array($legacy['state'] ?? '', ['completed', 'sending', 'unknown'], true)) {
            $result = ($legacy['state'] === 'completed') ? IntegrationResult::succeeded() : IntegrationResult::unknown('legacy_delivery_unresolved');
            $uid = $attempts->prepare($context, 'integration');
            $attempts->execute($uid, fn() => $result);
            $this->_saveProjection($integration, $submission, $result, $executionKey);
            return $result;
        }
        if ((new Query())->from(DeliveryAttempts::TABLE)->where(['submissionId' => $submission->id, 'binding' => $integration->handle, 'step' => 'integration', 'status' => ['unknown', 'sending']])->exists()) {
            $result = IntegrationResult::unknown('previous_delivery_unresolved');
            $this->_saveProjection($integration, $submission, $result, $executionKey);
            return $result;
        }
        $result = $attempts->execute($uid, function () use ($integration, $submission, $context, $uid, $attempts): IntegrationResult {
            $integration->setDeliveryContext($context, $uid);
            if ($context->execution === 'queued') {
                $integration->setQueueJob(new TriggerIntegration(['deliveryAttemptUid' => $uid]));
            }
            $attempts->checkpoint($uid, 'submission-projection', ['submissionId' => $submission->id, 'formId' => $submission->formId, 'values' => $submission->getValuesAsData()], $integration->getDiagnosticSecrets());
            $attempts->checkpoint($uid, 'mapping-inputs', ['settings' => array_intersect_key(['enabled' => $integration->getEnabled()] + $integration->getAttributes(), array_fill_keys($integration->getFormSettingAttributes(), true))], $integration->getDiagnosticSecrets());
            return Formie::$plugin->getIntegrations()->sendIntegrationPayload($integration, $submission);
        });
        $this->_saveProjection($integration, $submission, $result, $executionKey);
        $this->trigger(self::EVENT_RESULT, new IntegrationDeliveryEvent(['context' => $context, 'result' => $result, 'attemptUid' => $uid]));
        return $result;
    }


    private function _saveProjection(Integration|string $integration, Submission $submission, IntegrationResult $result, string $executionKey): void
    {
        $handle = is_string($integration) ? $integration : $integration->handle;
        $mutex = Craft::$app->getMutex();
        $lock = 'formie.delivery-projection.' . $submission->id;
        if (!$mutex->acquire($lock, 10)) {
            throw new RuntimeException('Unable to update delivery projection.');
        }
        try {
            $dispatcher = Formie::$plugin->getIntegrationDispatcher();
            $stored = (new Query())->select('integrationDispatchContext')->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $submission->id])->scalar();
            $submission->integrationDispatchContext = is_string($stored) ? Json::decode($stored) : ($stored ?: []);
            $context = $dispatcher->loadContext($submission);
            $value = $result->toStorage() + ['success' => $result->isSuccessful(), 'executionUid' => $executionKey, 'handle' => $handle];
            $element = is_string($integration) ? null : ($integration->context['dispatchElement'] ?? null);
            if (is_array($element) && $result->isSuccessful()) {
                $value += array_intersect_key($element, array_flip(['elementId', 'elementType', 'url']));
            }
            $previous = $context->getResult($handle);
            if (($previous['executionUid'] ?? null) === $executionKey && $result->isSuccessful()) {
                $value += array_intersect_key($previous, array_flip(['elementId', 'elementType', 'url']));
            }
            if (($value['elementType'] ?? null) === User::class && !empty($value['elementId']) && !$submission->userId) {
                $user = Craft::$app->getUsers()->getUserById((int)$value['elementId']);
                if ($user) {
                    $submission->setUser($user);
                    Craft::$app->getElements()->saveElement($submission, false);
                }
            }
            $context->record($handle, $value);
            $dispatcher->saveContext($submission, $context);
        } finally {
            $mutex->release($lock);
        }
    }
}
