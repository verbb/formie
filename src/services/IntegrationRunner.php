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
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\helpers\DeliveryAttempt;
use verbb\formie\helpers\IntegrationTriggerEvents;
use verbb\formie\jobs\TriggerIntegration;
use verbb\formie\models\FormIntegration;
use verbb\formie\models\IntegrationBatchResult;
use verbb\formie\models\IntegrationDispatchPlan;
use verbb\formie\models\IntegrationExecutionContext;
use verbb\formie\models\IntegrationResult;

use Craft;
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
            Formie::$plugin->getIntegrations()->getEnabledFormIntegrations($form),
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

        foreach (Formie::$plugin->getIntegrations()->getEnabledFormIntegrations($form) as $binding) {
            $available[$binding->integration->handle] = $binding;
        }

        $stopped = (bool)($triggerContext['skipRemaining'] ?? false);

        foreach ($handles as $handle) {
            $binding = $available[$handle] ?? null;

            if ($stopped || !$binding instanceof FormIntegration || !$binding->integration instanceof Integration || !$binding->integration->supportsPayloadSending()) {
                $result = IntegrationResult::skipped($stopped ? 'previous_step_failed' : 'disabled_or_missing');
                $context = $this->_executionContext($submission, $handle, $executionKey, $triggerContext['execution'] ?? 'synchronous', $triggerContext);
                $attempts = Formie::$plugin->getDeliveryAttempts();
                $uid = $attempts->prepare($context, 'integration');
                $result = $attempts->execute($uid, fn() => $result);
                $this->_saveProjection($handle, $submission, $result, $executionKey);
                $this->_reportResult($context, $result, $uid);
            } else {
                $integration = $binding->createRuntime();
                $execution = $triggerContext['execution'] ?? 'synchronous';
                $result = $this->runIntegration($integration, $submission, $executionKey, $execution, $triggerContext);
            }

            $batch->record($handle, $result);

            if (!in_array($result->status, [IntegrationStatus::Succeeded, IntegrationStatus::Skipped], true) && $plan?->shouldStopOnFailure()) {
                $batch->markStoppedOnFailure();
                $stopped = true;
            }
        }

        $this->trigger(self::EVENT_BATCH_COMPLETED, new IntegrationDeliveryEvent(['context' => $this->_executionContext($submission, '@dispatch', $executionKey, $triggerContext['execution'] ?? 'synchronous', $triggerContext), 'batch' => $batch]));

        return $batch;
    }

    public function runIntegration(Integration $connection, Submission $submission, string $executionKey, string $execution, array $triggerContext = []): IntegrationResult
    {
        $mutex = Craft::$app->getMutex();
        $lock = 'formie.binding.' . hash('sha256', $submission->id . ':' . $connection->handle);

        if (!$mutex->acquire($lock, 10)) {
            $result = IntegrationResult::failed('binding_running');
            $this->_reportResult($this->_executionContext($submission, (string)$connection->handle, $executionKey, $execution, $triggerContext), $result);

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
        $context = $this->_executionContext($submission, '@dispatch', $identity, 'queued', $triggerContext);
        $uid = Formie::$plugin->getDeliveryAttempts()->prepare($context, 'dispatch', [
            'handles' => array_values($handles), 'operation' => $operation->value,
            'triggerContext' => $triggerContext, 'afterNotifications' => $runAfterNotifications,
        ]);
        Queue::push(new TriggerIntegration(['deliveryAttemptUid' => $uid]), Formie::$plugin->getSettings()->queuePriority);
        Formie::$plugin->getDeliveryAttempts()->checkpoint($uid, 'queued');
        $this->trigger(self::EVENT_QUEUED, new IntegrationDeliveryEvent(['context' => $context, 'attemptUid' => $uid]));
    }

    public function runQueuedAttempt(string $uid): IntegrationResult
    {
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $row = $attempts->get($uid);
        $submission = Submission::find()->id($row['submissionId'])->status(null)->isIncomplete(null)->isSpam(null)->one();

        if (!$submission) {
            $result = $attempts->execute($uid, fn() => IntegrationResult::failed('submission_missing'));

            if ($row['step'] === 'integration') {
                $this->_reportResult($attempts->context($uid), $result, $uid);
            }

            return $result;
        }

        $sites = Craft::$app->getSites();
        $previousSite = $sites->getCurrentSite();
        $previousLanguage = Craft::$app->language;
        $previousLocale = Craft::$app->getLocale();

        try {
            Craft::$app->language = $submission->getSite()->language;
            Craft::$app->set('locale', Craft::$app->getI18n()->getLocaleById($submission->getSite()->language));
            Craft::$app->getSites()->setCurrentSite($submission->getSite());

            if ($row['step'] === 'integration') {
                foreach (Formie::$plugin->getIntegrations()->getEnabledFormIntegrations($submission->getForm()) as $binding) {
                    if ($binding->integration->handle === $row['binding']) {
                        // Always read current settings, so a corrected integration applies on retry.
                        $integration = $binding->createRuntime();
                        $original = $attempts->context($uid);
                        $result = $this->runIntegration($integration, $submission, $row['executionUid'], $row['execution'], [
                            'triggerEvent' => IntegrationTriggerEvents::SUBMIT,
                            'operatorInitiated' => true,
                            'retryAttemptUid' => $uid,
                            'authorityType' => $original->authorityType,
                            'authorityScope' => $original->authorityScope,
                            'actorId' => $original->actorId,
                        ]);
                        $this->finalizeDelivery($uid, $result);

                        return $result;
                    }
                }

                $result = $attempts->execute($uid, fn() => IntegrationResult::skipped('disabled_or_missing'));
                $this->_reportResult($attempts->context($uid), $result, $uid);

                return $result;
            }

            $data = $attempts->data($uid);

            return $attempts->execute($uid, function() use ($submission, $data, $row): IntegrationResult {
                $triggerContext = $data['triggerContext'];
                $triggerContext['execution'] = 'queued';
                $batch = $this->runSteps($submission, $data['handles'], $triggerContext, Formie::$plugin->getIntegrationDispatcher()->getPlan($submission->getForm()), $row['executionUid']);
                Formie::$plugin->getIntegrationDispatcher()->sendNotifications($submission, IntegrationDispatcher::PHASE_AFTER, $row['executionUid']);

                return $batch->accepts() ? IntegrationResult::succeeded() : IntegrationResult::failed('batch_failed');
            });
        } finally {
            $sites->setCurrentSite($previousSite);
            Craft::$app->language = $previousLanguage;
            Craft::$app->set('locale', $previousLocale);
        }
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

    /** @deprecated Deliveries always run with current settings. */
    public function dispatchFingerprint(Submission $submission, array $handles): string
    {
        return $this->dispatchFingerprints($submission, $handles)[0];
    }

    /** @deprecated Deliveries always run with current settings. */
    public function dispatchFingerprints(Submission $submission, array $handles): array
    {
        $configuration = [];

        foreach (Formie::$plugin->getIntegrations()->getEnabledFormIntegrations($submission->getForm()) as $binding) {
            if (in_array($binding->integration->handle, $handles, true)) {
                $configuration[$binding->integration->handle] = Formie::$plugin->getDeliveryAttempts()->integrationConfiguration($binding->integration, $binding->toSettings());
            }
        }

        $configuration['notifications'] = array_map(static fn($notification) => Formie::$plugin->getDeliveryAttempts()->notificationConfiguration($notification), $submission->getForm()->getNotifications());

        return Formie::$plugin->getDeliveryAttempts()->operationFingerprints($submission, $configuration);
    }


    // Private Methods
    // =========================================================================

    private function _runIntegration(Integration $connection, Submission $submission, string $executionKey, string $execution, array $triggerContext): IntegrationResult
    {
        $integration = clone $connection;
        $integration->populateSubmissionContext($submission);
        $conditionEvaluation = $integration->enableConditions ? ConditionsHelper::evaluate($integration->conditions ?? [], $submission, 'integration') : null;
        $invalidConditions = $conditionEvaluation && $conditionEvaluation->value === null;
        $eligible = $integration->shouldTrigger($submission, $triggerContext) && $integration->enforceOptInField($submission);
        $overrides = [];
        $reason = ($triggerContext['operatorInitiated'] ?? false) ? 'manual' : 'automatic';

        if ($triggerContext['force'] ?? false) {
            // Only code can force a run, but a web request still needs access to this form's submissions.
            $user = Craft::$app->getUser()->getIdentity();

            if (!Craft::$app->getRequest()->getIsConsoleRequest() && (!$user || !Formie::$plugin->getPermissions()->canSaveSubmissions($user, $submission->getForm()))) {
                throw new RuntimeException('You do not have permission to run integrations for this form.');
            }

            $reason = trim((string)($triggerContext['reason'] ?? '')) ?: 'force';
            $overrides = ['conditions', 'optIn'];
        }

        if (isset($triggerContext['retryAttemptUid'])) {
            $original = Formie::$plugin->getDeliveryAttempts()->context($triggerContext['retryAttemptUid']);

            if ($original->submissionId !== (int)$submission->id || $original->binding !== $integration->handle || $original->executionUid !== $executionKey) {
                throw new RuntimeException('Retry authority does not match this delivery.');
            }

            $overrides = $original->overrides;
            $reason = $original->reason;
            $triggerContext['authorityType'] = $original->authorityType;
            $triggerContext['authorityScope'] = $original->authorityScope;
            $triggerContext['actorId'] = $original->actorId;
        }

        $context = $this->_executionContext($submission, (string)$integration->handle, $executionKey, $execution, $triggerContext, $reason, $eligible, $overrides);
        $this->trigger(self::EVENT_EVALUATED, new IntegrationDeliveryEvent(['context' => $context]));

        if ($invalidConditions || (!$eligible && !$overrides)) {
            $result = $invalidConditions ? new IntegrationResult(IntegrationStatus::Failed, code: 'invalid_conditions', diagnostics: $conditionEvaluation->diagnostics) : IntegrationResult::skipped('conditions');
            $attempts = Formie::$plugin->getDeliveryAttempts();
            $uid = $attempts->prepare($context, 'integration');
            $result = $attempts->execute($uid, fn() => $result);
            $this->_saveProjection($integration, $submission, $result, $executionKey);
            $this->_reportResult($context, $result, $uid);

            return $result;
        }

        $integration->setScenario(Integration::SCENARIO_FORM);

        if (!$integration->validate($integration->getFormSettingAttributes())) {
            $result = IntegrationResult::failed('invalid_form_settings');
            $attempts = Formie::$plugin->getDeliveryAttempts();
            $uid = $attempts->prepare($context, 'integration');
            $result = $attempts->execute($uid, fn() => $result);
            $this->_saveProjection($integration, $submission, $result, $executionKey);
            $this->_reportResult($context, $result, $uid);

            return $result;
        }

        $attempts = Formie::$plugin->getDeliveryAttempts();
        $uid = $attempts->prepare($context, 'integration');
        $legacyResult = LegacyDeliveryAttempts::integrationResult($submission, $integration, $executionKey, $uid);

        if ($legacyResult) {
            $uid = $attempts->prepare($context, 'integration');
            $legacyResult = $attempts->execute($uid, fn() => $legacyResult);
            $this->_saveProjection($integration, $submission, $legacyResult, $executionKey);
            $this->_reportResult($context, $legacyResult, $uid);

            return $legacyResult;
        }

        $result = $attempts->execute($uid, function() use ($integration, $submission, $context, $uid, $attempts): IntegrationResult {
            $integration->setDeliveryContext($context, $uid);

            if ($context->execution === 'queued') {
                $integration->setQueueJob(new TriggerIntegration(['deliveryAttemptUid' => $uid]));
            }

            try {
                $attempts->checkpointSubmission($uid, $submission, $integration->getDiagnosticSecrets());
                $attempts->checkpoint($uid, 'mapping-inputs', ['settings' => FormIntegration::settingsFromRuntime($integration)], $integration->getDiagnosticSecrets());

                return Formie::$plugin->getIntegrations()->sendIntegrationPayload($integration, $submission);
            } catch (Throwable $error) {
                return $integration->resultForException($error);
            }
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

    private function _executionContext(
        Submission $submission,
        string $binding,
        string $executionUid,
        string $execution,
        array $triggerContext,
        string $reason = 'automatic',
        bool $eligible = true,
        array $overrides = [],
    ): IntegrationExecutionContext {
        return new IntegrationExecutionContext(
            (int)$submission->id,
            (int)$submission->formId,
            $binding,
            $executionUid,
            $execution,
            $reason,
            $eligible,
            $overrides,
            isset($triggerContext['authorityType']) ? (string)$triggerContext['authorityType'] : null,
            isset($triggerContext['authorityScope']) ? (string)$triggerContext['authorityScope'] : null,
            isset($triggerContext['actorId']) ? (int)$triggerContext['actorId'] : null,
        );
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
            $context = $dispatcher->loadRunResults($submission, $executionKey);
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
            $dispatcher->saveRunResults($submission, $context, $executionKey);
        } finally {
            $mutex->release($lock);
        }
    }
}
