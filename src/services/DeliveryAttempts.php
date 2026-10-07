<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\enums\IntegrationStatus;
use verbb\formie\errors\IntegrationStepException;
use verbb\formie\events\IntegrationDeliveryEvent;
use verbb\formie\helpers\DeliveryDiagnostics;
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
use RuntimeException;
use Throwable;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\StreamInterface;

/** Durable operation identities and append-only evidence, independent of Craft jobs. */
class DeliveryAttempts extends Component
{
    // Constants
    // =========================================================================

    public const TABLE = '{{%formie_delivery_attempts}}';
    public const DIAGNOSTICS = '{{%formie_delivery_diagnostics}}';
    public const EVENT_OPERATION_START = 'operationStart';
    public const EVENT_ATTEMPT_COMPLETED = 'attemptCompleted';
    public const EVENT_RETRY_DECISION = 'retryDecision';
    public const EVENT_RECONCILIATION = 'reconciliation';


    // Public Methods
    // =========================================================================

    public function prepare(IntegrationExecutionContext $context, string $step, array $data = [], ?string $parentUid = null): string
    {
        if ($context->submissionId <= 0) {
            throw new RuntimeException('Save the submission before preparing delivery.');
        }
        Formie::$plugin->getSubmissionDispatches()->ensureRun($context);
        $identity = hash('sha256', Json::encode([$context->submissionId, $context->binding, $context->executionUid, $step]));
        $uid = StringHelper::UUID();
        $now = Db::prepareDateForDb(new DateTime());
        $data['context'] = get_object_vars($context);
        Craft::$app->getDb()->createCommand()->upsert(self::TABLE, [
            'uid' => $uid, 'identity' => $identity, 'submissionId' => $context->submissionId,
            'formId' => $context->formId, 'binding' => $context->binding, 'step' => $step,
            'parentUid' => $parentUid, 'executionUid' => $context->executionUid,
            'execution' => $context->execution, 'status' => 'pending', 'requestKey' => StringHelper::UUID(),
            'data' => $this->_encrypt($data), 'dateCreated' => $now, 'dateUpdated' => $now,
        ], false)->execute();
        $row = (new Query())->from(self::TABLE)->where(['identity' => $identity])->one();

        if (!$row) {
            throw new RuntimeException('Unable to persist delivery identity.');
        }

        if ($row['uid'] === $uid) {
            $this->checkpoint($uid, 'prepared', ['step' => $step, 'execution' => $context->execution, 'reason' => $context->reason, 'actorId' => $context->overrides ? Craft::$app->getUser()->getId() : null, 'eligible' => $context->eligible, 'overrides' => $context->overrides]);
        }
        Formie::$plugin->getSubmissionDispatches()->refresh($context->submissionId, $context->executionUid);
        return $row['uid'];
    }

    public function get(string $uid): array
    {
        $row = Craft::$app->getDb()->useMaster(fn() => (new Query())->from(self::TABLE)->where(['uid' => $uid])->one());

        if (!$row) {
            throw new RuntimeException('Delivery attempt not found.');
        }
        return $row;
    }

    public function data(string $uid): array
    {
        return (array)$this->_decrypt($this->get($uid)['data']);
    }

    public function context(string $uid): IntegrationExecutionContext
    {
        $row = $this->get($uid);

        if ($row['data']) {
            return new IntegrationExecutionContext(...$this->data($uid)['context']);
        }
        return new IntegrationExecutionContext((int)$row['submissionId'], (int)$row['formId'], $row['binding'], $row['executionUid'], $row['execution'], 'evidence_expired');
    }

    public function checkpoint(string $uid, string $checkpoint, array $data = [], array $secrets = []): void
    {
        $row = $this->get($uid);

        // Keep history bounded without replacing earlier checkpoints. A final
        // status remains available even after the diagnostic checkpoint budget.
        if (!in_array($checkpoint, ['result', 'retry', 'reconciled', 'sensitive-export'], true) && (new Query())->from(self::DIAGNOSTICS)->where(['attemptId' => $row['id']])->count() >= 200) {
            if ((new Query())->from(self::DIAGNOSTICS)->where(['attemptId' => $row['id'], 'checkpoint' => 'evidence-truncated'])->exists()) {
                return;
            }
            $data = ['truncated' => true, 'reason' => 'checkpoint_limit', 'firstOmittedCheckpoint' => $checkpoint];
            $checkpoint = 'evidence-truncated';
        }
        $evidence = DeliveryDiagnostics::redactComplete($data, $secrets);
        $encoded = Json::encode($evidence);

        if (strlen($encoded) > 2097152) {
            $evidence = ['truncated' => true, 'reason' => 'checkpoint_byte_limit', 'originalBytes' => strlen($encoded), 'preview' => mb_strcut($encoded, 0, 65536, 'UTF-8')];
        }
        Craft::$app->getDb()->createCommand()->insert(self::DIAGNOSTICS, [
            'attemptId' => $row['id'], 'checkpoint' => $checkpoint,
            'data' => $this->_encrypt($evidence),
            'dateCreated' => Db::prepareDateForDb(new DateTime()),
        ])->execute();
    }

    public function checkpointSubmission(string $uid, Submission $submission, array $secrets = []): void
    {
        $stored = (new Query())->select('content')->from('{{%formie_submissions}}')->where(['id' => $submission->id])->scalar();
        $fields = array_map(static fn($field): array => [
            'uid' => $field->uid, 'handle' => $field->valueKey(), 'label' => $field->label, 'type' => get_class($field),
        ], $submission->getForm()->getFieldsRecursively());
        $this->checkpoint($uid, 'submission-projection', [
            'submissionId' => $submission->id, 'formId' => $submission->formId,
            'stateVersion' => $submission->stateVersion, 'fields' => $fields,
            'storedValues' => is_string($stored) ? Json::decode($stored) : $stored,
            'values' => $submission->getValuesAsData(), 'metadata' => $submission->getMetadata(),
        ], $secrets);
    }

    public function operationFingerprint(Submission $submission, array $configuration): string
    {
        $input = $this->_operationFingerprintData($submission, $configuration);
        return hash_hmac('sha256', Json::encode($input), Formie::$plugin->getSettings()->getSecurityKey());
    }

    public function integrationConfiguration(Integration $integration, array $settings): array
    {
        return [
            'binding' => $settings,
            'connection' => $integration->id ? (new Query())->select(['type', 'settings'])->from('{{%formie_integrations}}')->where(['id' => $integration->id])->one() : ['type' => get_class($integration)],
        ];
    }

    public function notificationConfiguration(Notification $notification): array
    {
        $config = $notification->getAttributes();
        // The durable attempt already owns the notification locator. A newly
        // saved model may not yet carry the database-assigned UID.
        unset($config['uid']);
        return $config;
    }

    public function executePrepared(string $uid, string $fingerprint, callable $send, array $secrets = []): IntegrationResult
    {
        return $this->execute($uid, function() use ($uid, $fingerprint, $send): IntegrationResult {
            $accepted = $this->data($uid)['acceptedFingerprint'] ?? null;

            if (!is_string($accepted) || !hash_equals($accepted, $fingerprint)) {
                $this->checkpoint($uid, 'operation-stale', ['reason' => $accepted === null ? 'missing_accepted_operation' : 'input_or_configuration_changed']);
                return IntegrationResult::rejected('operation_stale');
            }
            return $send();
        }, $secrets);
    }

    public function execute(string $uid, callable $send, array $secrets = []): IntegrationResult
    {
        if (Craft::$app->getDb()->getTransaction()?->getIsActive()) {
            throw new RuntimeException('Commit the transaction before external delivery.');
        }
        $mutex = Craft::$app->getMutex();
        $lock = 'formie.attempt.' . $uid;

        if (!$mutex->acquire($lock, 10)) {
            throw new RuntimeException('Delivery is already running.');
        }

        try {
            $row = $this->get($uid);

            if (in_array($row['status'], ['sending', 'unknown'], true)) {
                $result = IntegrationResult::unknown('reconciliation_required');

                if ($row['status'] === 'sending') {
                    $this->_finish($uid, $result);
                }
                return $result;
            }

            if ($row['result']) {
                $previous = IntegrationResult::fromStorage(Json::decode($row['result']));

                if (!$previous->retryable) {
                    return $previous;
                }

                if ((new Query())->from(self::DIAGNOSTICS)->where(['attemptId' => $row['id'], 'checkpoint' => 'retry'])->count() >= 25) {
                    return IntegrationResult::failed('retry_limit');
                }
                $this->checkpoint($uid, 'retry', ['previous' => $previous->toStorage()]);
                $this->_event(self::EVENT_RETRY_DECISION, $uid, $previous);
            }
            $now = Db::prepareDateForDb(new DateTime());
            Db::update(self::TABLE, ['status' => 'sending', 'startedAt' => $now, 'dateUpdated' => $now], ['uid' => $uid]);
            Formie::$plugin->getSubmissionDispatches()->refresh((int)$row['submissionId'], $row['executionUid']);
            $this->checkpoint($uid, 'started');
            $this->_event(self::EVENT_OPERATION_START, $uid);

            try {
                $result = $send($row['requestKey']);

                if (!$result instanceof IntegrationResult) {
                    $result = IntegrationResult::unknown('invalid_result');
                }
            } catch (Throwable $error) {
                $result = $error instanceof IntegrationStepException ? $error->result : IntegrationResult::fromException($error);
                $this->checkpoint($uid, 'exception', DeliveryDiagnostics::exception($error) + ['resultCode' => $result->code], $secrets);
            }
            $result = IntegrationResult::fromStorage(DeliveryDiagnostics::redactComplete($result->toStorage(), $secrets));
            $this->_finish($uid, $result);
            return $result;
        } finally {
            $mutex->release($lock);
        }
    }

    public function write(IntegrationExecutionContext $context, string $step, string $parentUid, array $payload, callable $send, array $metadata = [], array $secrets = []): mixed
    {
        $hash = hash_hmac('sha256', Json::encode($this->_canonicalize($payload)), Formie::$plugin->getSettings()->getSecurityKey());
        $uid = $this->prepare($context, $step, [], $parentUid);
        $this->checkpoint($uid, 'request', ['request' => $payload] + $metadata, $secrets);

        $result = $this->execute($uid, function(string $key) use ($uid, $hash, $send, $secrets): IntegrationResult {
            $row = $this->get($uid);

            if ($row['payloadHash'] !== null && !hash_equals($row['payloadHash'], $hash)) {
                return IntegrationResult::unknown('step_parameters_changed');
            }
            Db::update(self::TABLE, ['payloadHash' => $hash], ['uid' => $uid]);

            try {
                $response = $send($key);
            } catch (RequestException $error) {
                if ($error->getResponse()) {
                    $this->checkpoint($uid, 'response-error', ['status' => $error->getResponse()->getStatusCode(), 'body' => (string)$error->getResponse()->getBody()], $secrets);
                    Db::update(self::TABLE, ['response' => $this->_encrypt(['_httpFailure' => $error->getResponse()->getStatusCode(), 'body' => (string)$error->getResponse()->getBody()])], ['uid' => $uid]);
                }
                throw $error;
            }
            // A replay must return the same provider data to dependent steps. If
            // storing it fails after acceptance, the outcome stays unknown.
            Db::update(self::TABLE, ['response' => $this->_encrypt($response)], ['uid' => $uid]);
            $this->checkpoint($uid, 'response', ['body' => $response], $secrets);
            $decoded = is_string($response) ? json_decode($response, true) : $response;
            $providerId = is_array($decoded) ? ($decoded['id'] ?? $decoded['data']['id'] ?? null) : null;
            return IntegrationResult::succeeded(is_scalar($providerId) ? (string)$providerId : null);
        }, $secrets);
        $row = $this->get($uid);

        if ($row['payloadHash'] !== null && !hash_equals($row['payloadHash'], $hash)) {
            throw new IntegrationStepException(IntegrationResult::unknown('step_parameters_changed'));
        }

        if (!$result->isSuccessful()) {
            $failure = $row['response'] ? $this->_decrypt($row['response']) : null;
            $response = is_array($failure) && isset($failure['_httpFailure']) ? new Response($failure['_httpFailure'], [], $failure['body']) : null;
            throw new IntegrationStepException($result, response: $response);
        }

        if (!$row['response']) {
            throw new IntegrationStepException(IntegrationResult::unknown('confirmed_response_unavailable'));
        }
        return $this->_decrypt($row['response']);
    }

    public function recordResource(string $uid, array $resource): void
    {
        Db::update(self::TABLE, ['response' => $this->_encrypt($resource)], ['uid' => $uid]);
        $this->checkpoint($uid, 'provider-resource', $resource);
    }

    public function resource(string $uid): ?array
    {
        $response = $this->get($uid)['response'];
        return $response ? (array)$this->_decrypt($response) : null;
    }

    public function sendAlert(Submission $submission, string $binding, string $executionUid, string $recipient, callable $send): IntegrationResult
    {
        $context = new IntegrationExecutionContext((int)$submission->id, (int)$submission->formId, $binding, $executionUid);
        $uid = $this->prepare($context, 'alert:' . hash('sha256', $recipient));
        return $this->execute($uid, fn() => $send() ? IntegrationResult::succeeded() : IntegrationResult::unknown('mail_acceptance_unconfirmed'));
    }

    public function startDirect(IntegrationExecutionContext $context): string
    {
        $mutex = Craft::$app->getMutex();
        $lock = 'formie.binding.' . hash('sha256', $context->submissionId . ':' . $context->binding);

        if (!$mutex->acquire($lock, 10)) {
            throw new IntegrationStepException(IntegrationResult::unknown('binding_running'));
        }

        try {
            if ((new Query())->from(self::TABLE)->where(['submissionId' => $context->submissionId, 'binding' => $context->binding, 'step' => 'integration', 'status' => ['sending', 'unknown']])->exists()) {
                throw new IntegrationStepException(IntegrationResult::unknown('previous_delivery_unresolved'));
            }
            $uid = $this->prepare($context, 'integration');
            $now = Db::prepareDateForDb(new DateTime());
            Db::update(self::TABLE, ['status' => 'sending', 'startedAt' => $now, 'dateUpdated' => $now], ['uid' => $uid]);
            Formie::$plugin->getSubmissionDispatches()->refresh($context->submissionId, $context->executionUid);
            $this->checkpoint($uid, 'started');
            return $uid;
        } finally {
            $mutex->release($lock);
        }
    }

    public function completeDirect(string $uid, IntegrationResult $result): void
    {
        $this->_finish($uid, $result);
    }

    public function history(int $submissionId): array
    {
        return (new Query())->select(['uid', 'binding', 'step', 'parentUid', 'execution', 'status', 'result', 'startedAt', 'completedAt', 'dateCreated'])
            ->from(self::TABLE)->where(['submissionId' => $submissionId])->orderBy(['id' => SORT_DESC])->limit(250)->all();
    }

    public function supportBundle(string $uid): array
    {
        $row = $this->get($uid);
        unset($row['data'], $row['response'], $row['payloadHash'], $row['requestKey'], $row['identity']);
        $row = DeliveryDiagnostics::redact($row);
        $row['result'] = $row['result'] ? Json::decode($row['result']) : null;
        $row['retentionDays'] = Formie::$plugin->getSettings()->deliveryEvidenceRetentionDays;
        $row['checkpoints'] = $this->_supportCheckpoints((int)$row['id'], 200);
        $row['operations'] = [];
        $bytes = strlen(Json::encode($row));

        foreach ((new Query())->select(['id', 'uid', 'binding', 'step', 'parentUid', 'status', 'result', 'dateUpdated'])->from(self::TABLE)->where(['submissionId' => $row['submissionId'], 'executionUid' => $row['executionUid']])->andWhere(['not', ['uid' => $uid]])->orderBy(['id' => SORT_ASC])->limit(101)->all() as $operation) {
            if (count($row['operations']) === 100) {
                $row['truncated'] = true;
                break;
            }
            $operation = DeliveryDiagnostics::redact($operation);
            $operation['result'] = $operation['result'] ? Json::decode($operation['result']) : null;
            $operation['checkpoints'] = $this->_supportCheckpoints((int)$operation['id'], 20);
            unset($operation['id']);
            $bytes += strlen(Json::encode($operation));

            if ($bytes > 1048576) {
                $row['truncated'] = true;
                break;
            }
            $row['operations'][] = $operation;
        }
        return $row;
    }

    public function sensitiveEvidence(string $uid): array
    {
        if (!Craft::$app->getUser()->checkPermission('formie-exportSensitiveDeliveryEvidence')) {
            throw new RuntimeException('Sensitive evidence permission is required.');
        }
        $row = $this->get($uid);
        $this->checkpoint($uid, 'sensitive-export', ['actorId' => Craft::$app->getUser()->getId()]);
        $attempt = $row;
        unset($attempt['response'], $attempt['payloadHash'], $attempt['requestKey'], $attempt['identity']);
        $attempt['data'] = $row['data'] ? DeliveryDiagnostics::redactComplete($this->_decrypt($row['data'])) : null;
        $attempt['result'] = $row['result'] ? Json::decode($row['result']) : null;

        return [
            'uid' => $uid,
            'attempt' => $attempt,
            'checkpoints' => $this->_evidenceCheckpoints((int)$row['id'], 200),
        ];
    }

    public function reconcile(string $uid, IntegrationResult $result, string $reason): void
    {
        if (!Craft::$app->getUser()->checkPermission('formie-reconcileDeliveries') || trim($reason) === '') {
            throw new RuntimeException('Delivery reconciliation requires permission and a reason.');
        }
        $mutex = Craft::$app->getMutex();
        $lock = 'formie.attempt.' . $uid;

        if (!$mutex->acquire($lock, 0)) {
            throw new RuntimeException('The delivery is running. Wait for it to finish before reconciliation.');
        }

        try {
            if (!in_array($this->get($uid)['status'], ['unknown', 'sending'], true) || $result->status === IntegrationStatus::Unknown) {
                throw new RuntimeException('This delivery cannot be reconciled to that state.');
            }

            if ((new Query())->from(self::TABLE)->where(['parentUid' => $uid, 'status' => ['unknown', 'sending']])->exists()) {
                throw new RuntimeException('Reconcile uncertain child operations first.');
            }
            $this->checkpoint($uid, 'reconciled', ['actorId' => Craft::$app->getUser()->getId(), 'reason' => $reason, 'result' => $result->toStorage()]);
            $this->_finish($uid, $result);
            $this->_event(self::EVENT_RECONCILIATION, $uid, $result);
            Formie::$plugin->getIntegrationRunner()->finalizeDelivery($uid, $result);
        } finally {
            $mutex->release($lock);
        }
    }

    public function hasReconciliation(string $uid): bool
    {
        return (new Query())->from(self::DIAGNOSTICS)->where(['attemptId' => $this->get($uid)['id'], 'checkpoint' => 'reconciled'])->exists();
    }

    public function purgeExpiredEvidence(): void
    {
        $days = max(1, Formie::$plugin->getSettings()->deliveryEvidenceRetentionDays);
        $before = Db::prepareDateForDb(new DateTime('-' . $days . ' days'));
        Craft::$app->getDb()->createCommand()->delete('{{%formie_subscription_diagnostics}}', ['and', ['<', 'observedAt', $before], ['not in', 'status', ['pending', 'unknown', 'failed', 'pastDue']]])->execute();
        // Retain operation identities/results to prevent replay after evidence expiry.
        Db::update(self::TABLE, ['data' => null, 'response' => null], ['and', ['<', 'dateUpdated', $before], ['not in', 'status', ['pending', 'sending', 'unknown', 'failed']]], updateTimestamp: false);
        $completed = (new Query())->select('id')->from(self::TABLE)->where(['<', 'dateUpdated', $before])->andWhere(['not in', 'status', ['pending', 'sending', 'unknown', 'failed']]);
        Craft::$app->getDb()->createCommand()->delete(self::DIAGNOSTICS, ['and', ['attemptId' => $completed], ['<', 'dateCreated', $before], ['not in', 'checkpoint', ['prepared', 'result', 'retry', 'reconciled', 'sensitive-export', 'support-export']]])->execute();
    }


    // Private Methods
    // =========================================================================

    private function _operationFingerprintData(Submission $submission, array $configuration): array
    {
        $form = $submission->getForm();
        $content = (new Query())->select('content')->from('{{%formie_submissions}}')->where(['id' => $submission->id])->scalar();
        $fields = array_map(static function($field): array {
            $settings = $field->getSettings();

            // The layout loader normalizes these optional bags to empty arrays.
            foreach (['containerAttributes', 'inputAttributes'] as $attribute) {
                $settings[$attribute] = $settings[$attribute] ?? [];
            }
            return ['type' => get_class($field), 'uid' => $field->uid, 'handle' => $field->valueKey(), 'settings' => $settings];
        }, $form->getFieldsRecursively());
        $input = [
            'content' => is_string($content) ? Json::decode($content) : $content,
            'title' => $submission->title, 'siteId' => $submission->siteId,
            'statusId' => $submission->statusId, 'fields' => $fields,
            'settings' => $form->getSettings()->getAttributes(), 'configuration' => $configuration,
        ];
        // Hash the inert shape, not object identity or ordering of associative keys.
        return $this->_canonicalize(Json::decode(Json::encode($input)));
    }

    private function _supportCheckpoints(int $attemptId, int $limit): array
    {
        $checkpoints = [];
        $bytes = 0;

        foreach ($this->_evidenceCheckpoints($attemptId, $limit) as $checkpoint) {
            $bytes += strlen(Json::encode($checkpoint['data']));

            if ($bytes > 524288) {
                $checkpoints[] = ['checkpoint' => 'truncated', 'data' => ['reason' => 'support_bundle_limit']];
                break;
            }
            $checkpoints[] = $checkpoint;
        }
        return $checkpoints;
    }

    private function _evidenceCheckpoints(int $attemptId, int $limit): array
    {
        $checkpoints = [];
        $rows = (new Query())->select(['checkpoint', 'data', 'dateCreated'])->from(self::DIAGNOSTICS)->where(['attemptId' => $attemptId])->orderBy(['id' => SORT_DESC])->limit($limit + 1)->all();
        $omitted = count($rows) > $limit;

        foreach (array_slice($rows, 0, $limit) as $checkpoint) {
            $checkpoint['data'] = $this->_decrypt($checkpoint['data']);
            $checkpoints[] = $checkpoint;
        }

        $checkpoints = array_reverse($checkpoints);

        if ($omitted) {
            array_unshift($checkpoints, ['checkpoint' => 'truncated', 'data' => ['truncated' => true, 'reason' => 'checkpoint_export_limit']]);
        }
        return $checkpoints;
    }

    private function _finish(string $uid, IntegrationResult $result): void
    {
        $now = Db::prepareDateForDb(new DateTime());
        $this->checkpoint($uid, 'result', $result->toStorage());
        Db::update(self::TABLE, ['status' => $result->status->value, 'result' => DeliveryDiagnostics::encode($result->toStorage()), 'completedAt' => $now, 'dateUpdated' => $now], ['uid' => $uid]);
        $row = $this->get($uid);
        Formie::$plugin->getSubmissionDispatches()->refresh((int)$row['submissionId'], $row['executionUid']);
        $this->_event(self::EVENT_ATTEMPT_COMPLETED, $uid, $result);
    }

    private function _event(string $name, string $uid, ?IntegrationResult $result = null): void
    {
        $this->trigger($name, new IntegrationDeliveryEvent(['context' => $this->context($uid), 'result' => $result, 'attemptUid' => $uid]));
    }

    private function _encrypt(mixed $data): string
    {
        $plain = Json::encode($data);

        if (strlen($plain) > 2097152) {
            throw new RuntimeException('Delivery data exceeds the retention limit.');
        }
        return base64_encode(Craft::$app->getSecurity()->encryptByKey($plain, Formie::$plugin->getSettings()->getSecurityKey()));
    }

    private function _decrypt(?string $cipher): mixed
    {
        if (!$cipher) {
            throw new RuntimeException('Delivery evidence expired or is unavailable. Reconciliation is required.');
        }
        $plain = Craft::$app->getSecurity()->decryptByKey(base64_decode($cipher, true), Formie::$plugin->getSettings()->getSecurityKey());

        if ($plain === false) {
            throw new RuntimeException('Unable to decrypt delivery evidence.');
        }
        return Json::decode($plain);
    }

    private function _canonicalize(array $value): array
    {
        if (!array_is_list($value)) {
            ksort($value);
        }

        foreach ($value as &$item) {
            if (is_array($item)) {
                $item = $this->_canonicalize($item);
            } elseif ($item instanceof StreamInterface) {
                if (!$item->isSeekable()) {
                    throw new RuntimeException('Delivery streams must be seekable.');
                }
                $position = $item->tell();
                $itemHash = hash('sha256', $item->getContents());
                $item->seek($position);
                $item = ['streamHash' => $itemHash];
            } elseif (is_resource($item) && get_resource_type($item) === 'stream') {
                $position = ftell($item);

                if ($position === false || !stream_get_meta_data($item)['seekable']) {
                    throw new RuntimeException('Delivery streams must be seekable.');
                }
                $itemHash = hash_init('sha256');
                hash_update_stream($itemHash, $item);
                fseek($item, $position);
                $item = ['streamHash' => hash_final($itemHash)];
            }
        }
        return $value;
    }
}
