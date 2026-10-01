<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\compatibility\delivery\LegacyDeliveryAttempts;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\enums\IntegrationStatus;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\events\IntegrationDeliveryEvent;
use verbb\formie\helpers\DeliveryAttempt;
use verbb\formie\helpers\IntegrationTriggerEvents;
use verbb\formie\jobs\TriggerIntegration;
use verbb\formie\models\FormIntegration;
use verbb\formie\models\IntegrationBatchResult;
use verbb\formie\models\IntegrationDispatchPlan;
use verbb\formie\models\IntegrationExecutionContext;
use verbb\formie\models\IntegrationResult;

use Craft;
use craft\db\Query;
use craft\elements\User;
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

    public function resolveEnabledHandles(Form $form): array
    {
        return array_values(array_map(fn(FormIntegration $binding) => $binding->integration->handle, array_filter(
            Formie::$plugin->getIntegrations()->getFormIntegrationsForForm($form),
            fn(FormIntegration $binding) => $binding->integration->supportsPayloadSending(),
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

        foreach (Formie::$plugin->getIntegrations()->getFormIntegrationsForForm($form) as $binding) {
            $available[$binding->integration->handle] = $binding;
        }
        $stopped = (bool)($triggerContext['skipRemaining'] ?? false);

        foreach ($handles as $handle) {
            $binding = $available[$handle] ?? null;

            if ($stopped || !$binding instanceof FormIntegration || !$binding->integration instanceof Integration || !$binding->integration->supportsPayloadSending()) {
                $result = IntegrationResult::skipped($stopped ? 'previous_step_failed' : 'disabled_or_missing');
                $context = new IntegrationExecutionContext((int)$submission->id, (int)$submission->formId, $handle, $executionKey, $triggerContext['execution'] ?? 'synchronous');
                $attempts = Formie::$plugin->getDeliveryAttempts();
                $uid = $attempts->prepare($context, 'integration');
                $attempts->execute($uid, fn() => $result);
                $this->_saveProjection($handle, $submission, $result, $executionKey);
                $this->_reportResult($context, $result, $uid);
                $batch->record($handle, $result);
                continue;
            }
            $integration = $binding->createRuntime();

            if (isset($triggerContext['bindings'][$handle])) {
                $integration = FormIntegration::fromSettings($binding->integration, $triggerContext['bindings'][$handle], $form->getId(), $form->getHandle())->createRuntime();
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
            $result = IntegrationResult::unknown('binding_running');
            $this->_reportResult(new IntegrationExecutionContext((int)$submission->id, (int)$submission->formId, (string)$connection->handle, $executionKey, $execution), $result);
            return $result;
        }

        try {
            return Formie::$plugin->getIntegrationDispatcher()->withRun(
                $submission,
                $executionKey,
                fn() => $this->_runIntegration($connection, $submission, $executionKey, $execution, $triggerContext)
            );
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

        foreach (Formie::$plugin->getIntegrations()->getFormIntegrationsForForm($submission->getForm()) as $binding) {
            $bindings[$binding->integration->handle] = $binding->toSettings();
        }
        $triggerContext['bindings'] = $bindings;
        $uid = Formie::$plugin->getDeliveryAttempts()->prepare($context, 'dispatch', [
            'handles' => array_values($handles), 'operation' => $operation->value,
            'triggerContext' => $triggerContext, 'afterNotifications' => $runAfterNotifications,
            'acceptedFingerprint' => $this->dispatchFingerprint($submission, $handles),
        ]);
        (new DeliveryAttempt((int)$submission->id, 'integration-queue', $identity))->execute(['attemptUid' => $uid], function() use ($uid, $context): bool {
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
            $result = $attempts->execute($uid, fn() => IntegrationResult::rejected('submission_missing'));

            if ($row['step'] === 'integration') {
                $this->_reportResult($attempts->context($uid), $result, $uid);
            }
            return $result;
        }
        Craft::$app->language = $submission->getSite()->language;
        Craft::$app->set('locale', Craft::$app->getI18n()->getLocaleById($submission->getSite()->language));
        Craft::$app->getSites()->setCurrentSite($submission->getSite());

        if ($row['step'] === 'integration') {
            foreach (Formie::$plugin->getIntegrations()->getFormIntegrationsForForm($submission->getForm()) as $binding) {
                if ($binding->integration->handle === $row['binding']) {
                    $integration = FormIntegration::fromSettings($binding->integration, $data['settings'] ?? [], $submission->formId, $submission->getForm()->handle)->createRuntime();
                    $result = $this->runIntegration($integration, $submission, $row['executionUid'], $row['execution'], ['triggerEvent' => IntegrationTriggerEvents::SUBMIT, 'operatorInitiated' => true, 'retryAttemptUid' => $uid]);
                    $this->finalizeDelivery($uid, $result);
                    return $result;
                }
            }
            $result = $attempts->execute($uid, fn() => IntegrationResult::skipped('disabled_or_missing'));
            $this->_reportResult($attempts->context($uid), $result, $uid);
            return $result;
        }
        return $attempts->executePrepared($uid, $this->dispatchFingerprint($submission, $data['handles']), function() use ($submission, $data, $row): IntegrationResult {
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

    public function dispatchFingerprint(Submission $submission, array $handles): string
    {
        $configuration = [];

        foreach (Formie::$plugin->getIntegrations()->getFormIntegrationsForForm($submission->getForm()) as $binding) {
            if (in_array($binding->integration->handle, $handles, true)) {
                $configuration[$binding->integration->handle] = Formie::$plugin->getDeliveryAttempts()->integrationConfiguration($binding->integration, $binding->toSettings());
            }
        }
        $configuration['notifications'] = array_map(static fn($notification) => Formie::$plugin->getDeliveryAttempts()->notificationConfiguration($notification), $submission->getForm()->getNotifications());
        return Formie::$plugin->getDeliveryAttempts()->operationFingerprint($submission, $configuration);
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
            $attempts = Formie::$plugin->getDeliveryAttempts();
            $uid = $attempts->prepare($context, 'integration');
            $attempts->execute($uid, fn() => $result);
            $this->_saveProjection($integration, $submission, $result, $executionKey);
            $this->_reportResult($context, $result, $uid);
            return $result;
        }
        $integration->setScenario(Integration::SCENARIO_FORM);

        if (!$integration->validate($integration->getFormSettingAttributes())) {
            $result = IntegrationResult::rejected('invalid_form_settings');
            $attempts = Formie::$plugin->getDeliveryAttempts();
            $uid = $attempts->prepare($context, 'integration');
            $attempts->execute($uid, fn() => $result);
            $this->_saveProjection($integration, $submission, $result, $executionKey);
            $this->_reportResult($context, $result, $uid);
            return $result;
        }
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $settings = FormIntegration::settingsFromRuntime($integration);
        $fingerprint = $attempts->operationFingerprint($submission, $attempts->integrationConfiguration($integration, $settings));
        $uid = $attempts->prepare($context, 'integration', ['settings' => $settings, 'acceptedFingerprint' => $fingerprint]);
        $legacyResult = LegacyDeliveryAttempts::integrationResult($submission, $integration, $executionKey, $uid);

        if ($legacyResult) {
            $uid = $attempts->prepare($context, 'integration');
            $attempts->execute($uid, fn() => $legacyResult);
            $this->_saveProjection($integration, $submission, $legacyResult, $executionKey);
            $this->_reportResult($context, $legacyResult, $uid);
            return $legacyResult;
        }

        if ((new Query())->from(DeliveryAttempts::TABLE)->where(['submissionId' => $submission->id, 'binding' => $integration->handle, 'step' => 'integration', 'status' => ['unknown', 'sending']])->exists()) {
            $result = IntegrationResult::unknown('previous_delivery_unresolved');
            $this->_saveProjection($integration, $submission, $result, $executionKey);
            $this->_reportResult($context, $result, $uid);
            return $result;
        }
        $result = $attempts->executePrepared($uid, $fingerprint, function() use ($integration, $submission, $context, $uid, $attempts): IntegrationResult {
            $integration->setDeliveryContext($context, $uid);

            if ($context->execution === 'queued') {
                $integration->setQueueJob(new TriggerIntegration(['deliveryAttemptUid' => $uid]));
            }
            $attempts->checkpointSubmission($uid, $submission, $integration->getDiagnosticSecrets());
            $attempts->checkpoint($uid, 'mapping-inputs', ['settings' => FormIntegration::settingsFromRuntime($integration)], $integration->getDiagnosticSecrets());
            return Formie::$plugin->getIntegrations()->sendIntegrationPayload($integration, $submission);
        }, $integration->getDiagnosticSecrets());
        $this->_saveProjection($integration, $submission, $result, $executionKey);
        $this->_reportResult($context, $result, $uid);
        return $result;
    }

    private function _reportResult(IntegrationExecutionContext $context, IntegrationResult $result, ?string $uid = null): void
    {
        if ($result->status === IntegrationStatus::Skipped) {
            $this->trigger(self::EVENT_SKIPPED, new IntegrationDeliveryEvent(['context' => $context, 'result' => $result, 'attemptUid' => $uid]));
        }
        $this->trigger(self::EVENT_RESULT, new IntegrationDeliveryEvent(['context' => $context, 'result' => $result, 'attemptUid' => $uid]));
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
            $context = $dispatcher->loadContext($submission, $executionKey);
            $value = $result->toStorage() + ['success' => $result->isSuccessful(), 'executionUid' => $executionKey, 'handle' => $handle];
            $element = $result->outputs;

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
            $dispatcher->saveContext($submission, $context, $executionKey);
        } finally {
            $mutex->release($lock);
        }
    }
}
