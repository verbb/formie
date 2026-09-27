<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\Payment;
use verbb\formie\events\PaymentWebhookLifecycleEvent;
use verbb\formie\helpers\Table;
use verbb\formie\jobs\ProcessPaymentWebhook;
use verbb\formie\models\payments\PaymentWebhookCommand;
use verbb\formie\models\payments\PaymentWebhookReceipt;
use verbb\formie\models\payments\VerifiedWebhook;
use verbb\formie\models\payments\VerifiedWebhookBatch;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Queue;

use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

use RuntimeException;
use Throwable;

class PaymentWebhooks extends Component
{
    // Constants
    // =========================================================================

    private const RETRY_DELAYS = [60, 300, 900, 3600, 14400, 43200];
    private const MAX_ATTEMPTS = 8;
    private const STALE_SCHEDULE_SECONDS = 600;


    // Public Methods
    // =========================================================================

    public function receive(Payment $integration, PaymentWebhookCommand $request, bool $synchronous = false): Response
    {
        if ($integration->id && $request->integrationId !== (int)$integration->id) {
            throw new RuntimeException('Webhook integration mismatch.');
        }
        if (Craft::$app->getDb()->getTransaction()?->getIsActive()) {
            throw new RuntimeException('Payment webhook intake requires an independent durable transaction.');
        }

        // Preserve the Formie 3 event name with the richer lifecycle event so
        // legacy listeners remain compatible without introducing a second path.
        $this->_trigger($integration, Payment::EVENT_BEFORE_PROCESS_WEBHOOK, request: $request);
        $this->_trigger($integration, Payment::EVENT_BEFORE_VERIFY_WEBHOOK, request: $request);
        $processing = false;

        try {
            $batch = $integration->verifyWebhook($request);
            $this->_trigger($integration, Payment::EVENT_AFTER_VERIFY_WEBHOOK, $request, $batch);
            $receiptIds = [];
            $transaction = Craft::$app->getDb()->beginTransaction();

            try {
                foreach ($batch->events as $event) {
                    $receiptIds[] = $this->_persist($integration, $request, $batch->accountFingerprint, $batch->environment, $batch->evidenceHeaders, $event);
                }

                $transaction->commit();
            } catch (Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }

            // Provider acknowledgement is issued only after the whole verified
            // batch belongs to the durable inbox. Domain handling starts later.
            foreach ($receiptIds as $receiptId) {
                if ($synchronous) {
                    $processing = true;
                    $this->process($receiptId);
                    $processing = false;
                } else {
                    try {
                        $this->schedule($receiptId);
                    } catch (Throwable $queueError) {
                        // Durable ownership has already been established. A
                        // provider retry cannot repair our queue, so acknowledge
                        // the request and leave recovery to the inbox.
                        $row = $this->_row($receiptId);
                        $this->_trigger(
                            $integration,
                            Payment::EVENT_WEBHOOK_FAILED,
                            receipt: $row ? $this->_receipt($row) : null,
                            error: $queueError,
                        );
                        Formie::error('Unable to schedule payment webhook receipt {receiptId}: {message}', [
                            'receiptId' => $receiptId,
                            'message' => $queueError->getMessage(),
                        ]);
                    }
                }
            }

            return $integration->getWebhookAcknowledgement();
        } catch (Throwable $e) {
            // Processing owns its failure event because it can include the
            // durable receipt. Intake failures have request evidence only.
            if (!$processing) {
                $this->_trigger($integration, Payment::EVENT_WEBHOOK_FAILED, $request, error: $e);
            }
            throw $e;
        }
    }

    public function process(int $receiptId): void
    {
        $lock = 'formie.payment-webhook-receipt.' . $receiptId;
        $mutex = Craft::$app->getMutex();
        $resourceLock = null;
        $resourceLockAcquired = false;

        if (!$mutex->acquire($lock, 10)) {
            throw new RuntimeException('Payment webhook receipt is already being processed.');
        }

        try {
            $row = $this->_row($receiptId);

            if (!$row || in_array($row['status'], ['processed', 'ignored', 'failed'], true)) {
                return;
            }

            if ($row['nextAttemptAt'] && strtotime($row['nextAttemptAt'] . ' UTC') > time()) {
                return;
            }

            $this->_transition($receiptId, 'processing', [
                'attempts' => (int)$row['attempts'] + 1,
                'startedAt' => gmdate('Y-m-d H:i:s'),
                'nextAttemptAt' => null,
                'error' => null,
            ]);

            $row = $this->_row($receiptId);
            $receipt = $this->_receipt($row);
            $integration = Formie::$plugin->getIntegrations()->getIntegrationByUid($row['integrationUid']);

            if (!$integration instanceof Payment || (int)$integration->id !== (int)$row['integrationId']) {
                throw new RuntimeException('Payment webhook integration is unavailable.');
            }

            if ($receipt->resourceReference !== null && $receipt->resourceReference !== '') {
                $resourceLock = 'formie.payment-webhook-resource.' . hash('sha256', implode('|', [
                    $receipt->integrationUid,
                    $receipt->resourceType ?? 'resource',
                    $receipt->resourceReference,
                ]));
                $resourceLockAcquired = $mutex->acquire($resourceLock, 10);

                if (!$resourceLockAcquired) {
                    throw new RuntimeException('Payment webhook resource is already being processed.');
                }
            }

            Formie::$plugin->getPayments()->observeProvider(fn() => $integration->handleWebhook($receipt));
            $this->_transition($receiptId, 'processed', [
                'processedAt' => gmdate('Y-m-d H:i:s'),
            ]);
            $this->_trigger($integration, Payment::EVENT_AFTER_PROCESS_WEBHOOK, receipt: $this->_receipt($this->_row($receiptId)));
        } catch (Throwable $e) {
            $row = $this->_row($receiptId);

            if ($row && !in_array($row['status'], ['processed', 'ignored'], true)) {
                $attempts = max(1, (int)$row['attempts']);
                $delay = self::RETRY_DELAYS[min($attempts - 1, count(self::RETRY_DELAYS) - 1)];
                $terminal = $attempts >= self::MAX_ATTEMPTS;
                $this->_transition($receiptId, $terminal ? 'failed' : 'retryable', [
                    'nextAttemptAt' => $terminal ? null : gmdate('Y-m-d H:i:s', time() + $delay),
                    'error' => $terminal
                        ? 'Processing exhausted automatic retries; see queue failure diagnostics.'
                        : 'Processing failed; see queue failure diagnostics.',
                ]);

                $integration = Formie::$plugin->getIntegrations()->getIntegrationByUid((string)$row['integrationUid']);
                if ($integration instanceof Payment) {
                    $this->_trigger($integration, Payment::EVENT_WEBHOOK_FAILED, receipt: $this->_receipt($this->_row($receiptId)), error: $e);
                }

                if (!$terminal) {
                    try {
                        $retryRow = $this->_row($receiptId);

                        if ($retryRow) {
                            $this->_enqueue($receiptId, $retryRow, $delay);
                        }
                    } catch (Throwable $queueError) {
                        Formie::error('Unable to schedule payment webhook receipt {receiptId} retry: {message}', [
                            'receiptId' => $receiptId,
                            'message' => $queueError->getMessage(),
                        ]);
                    }
                }
            }

            throw $e;
        } finally {
            if ($resourceLockAcquired && $resourceLock !== null) {
                $mutex->release($resourceLock);
            }

            $mutex->release($lock);
        }
    }

    public function schedule(int $receiptId): bool
    {
        $lock = 'formie.payment-webhook-receipt.' . $receiptId;
        $mutex = Craft::$app->getMutex();

        if (!$mutex->acquire($lock, 10)) {
            return false;
        }

        try {
            $row = $this->_row($receiptId);

            if (!$row || in_array($row['status'], ['processed', 'ignored', 'failed'], true)) {
                return false;
            }

            if ($row['nextAttemptAt'] && strtotime($row['nextAttemptAt'] . ' UTC') > time()) {
                return false;
            }

            if ($row['status'] === 'scheduled' && $row['scheduledAt'] && strtotime($row['scheduledAt'] . ' UTC') > time() - self::STALE_SCHEDULE_SECONDS) {
                return false;
            }

            if ($row['status'] === 'processing' && $row['startedAt'] && strtotime($row['startedAt'] . ' UTC') > time() - self::STALE_SCHEDULE_SECONDS) {
                return false;
            }

            return $this->_enqueue($receiptId, $row);
        } finally {
            $mutex->release($lock);
        }
    }

    public function recover(int $limit = 100): int
    {
        $now = gmdate('Y-m-d H:i:s');
        $stale = gmdate('Y-m-d H:i:s', time() - self::STALE_SCHEDULE_SECONDS);
        $rows = (new Query())
            ->select('id')
            ->from(Table::FORMIE_WEBHOOK_RECEIPTS)
            ->where(['or',
                ['status' => 'verified'],
                ['and', ['status' => 'retryable'], ['or', ['nextAttemptAt' => null], ['<=', 'nextAttemptAt', $now]]],
                ['and', ['status' => 'scheduled'], ['or', ['nextAttemptAt' => null], ['<=', 'nextAttemptAt', $now]], ['<', 'scheduledAt', $stale]],
                ['and', ['status' => 'processing'], ['<', 'startedAt', $stale]],
            ])
            ->orderBy(['id' => SORT_ASC])
            ->limit(max(1, min(500, $limit)))
            ->column();
        $count = 0;

        foreach ($rows as $receiptId) {
            if ($this->schedule((int)$receiptId)) {
                $count++;
            }
        }

        return $count;
    }

    public function evidence(int $receiptId): array
    {
        if (!Craft::$app->getRequest()->getIsConsoleRequest() && !Formie::$plugin->getPermissions()->canAccessIntegrations(Craft::$app->getUser()->getIdentity())) {
            throw new ForbiddenHttpException('Payment evidence is restricted.');
        }

        $row = $this->_row($receiptId);

        if (!$row) {
            throw new RuntimeException('Webhook receipt not found.');
        }

        return [
            'body' => $this->_decrypt($row['body']),
            'headers' => Json::decode($this->_decrypt($row['headers'])),
            'payload' => Json::decode($this->_decrypt($row['payload'])),
        ];
    }


    // Private Methods
    // =========================================================================

    private function _persist(Payment $integration, PaymentWebhookCommand $request, string $accountFingerprint, string $environment, array $evidenceHeaders, VerifiedWebhook $event): int
    {
        if (!$integration->id || !$integration->uid) {
            throw new RuntimeException('A verified webhook requires a saved integration identity.');
        }
        if ($event->providerEventId === '') {
            throw new RuntimeException('A verified webhook requires a provider event identity.');
        }

        $identity = hash('sha256', $integration::class . '|' . $accountFingerprint . '|' . $environment . '|' . $event->providerEventId);
        $eventJson = Json::encode($event->payload);
        $eventHash = hash('sha256', $event->fingerprint ?? $eventJson);
        $lock = 'formie.payment-webhook-identity.' . $identity;
        $mutex = Craft::$app->getMutex();

        if (!$mutex->acquire($lock, 10)) {
            throw new RuntimeException('Payment webhook receipt is being recorded.');
        }

        try {
            $row = Craft::$app->getDb()->useMaster(fn() => (new Query())->from(Table::FORMIE_WEBHOOK_RECEIPTS)->where(['identity' => $identity])->one());

            if ($row) {
                if (!hash_equals((string)$row['eventHash'], $eventHash)) {
                    throw new RuntimeException('Webhook identity was reused with different evidence.');
                }

                return (int)$row['id'];
            }

            $now = gmdate('Y-m-d H:i:s');
            Craft::$app->getDb()->createCommand()->insert(Table::FORMIE_WEBHOOK_RECEIPTS, [
                'identity' => $identity,
                'integrationId' => $integration->id,
                'integrationUid' => $integration->uid,
                'accountFingerprint' => $accountFingerprint,
                'environment' => $environment,
                'eventId' => $event->providerEventId,
                'eventType' => $event->eventType,
                'resourceType' => $event->resourceType,
                'resourceReference' => $event->resourceReference,
                'providerCreatedAt' => $event->providerCreatedAt?->getTimestamp(),
                'bodyHash' => hash('sha256', $request->body),
                'eventHash' => $eventHash,
                'history' => Json::encode([['status' => 'verified', 'at' => gmdate('c')]]),
                'body' => $this->_encrypt($request->body),
                'headers' => $this->_encrypt(Json::encode($evidenceHeaders)),
                'payload' => $this->_encrypt($eventJson),
                'display' => $this->_display($event),
                'status' => 'verified',
                'attempts' => 0,
                'receivedAt' => $now,
                'verifiedAt' => $now,
            ])->execute();

            return (int)Craft::$app->getDb()->getLastInsertID();
        } finally {
            $mutex->release($lock);
        }
    }

    private function _enqueue(int $receiptId, array $row, int $delay = 0): bool
    {
        $previousStatus = (string)$row['status'];

        try {
            $this->_transition($receiptId, 'scheduled', [
                'scheduledAt' => gmdate('Y-m-d H:i:s'),
            ]);
            Queue::push(
                new ProcessPaymentWebhook(['receiptId' => $receiptId]),
                Formie::$plugin->getSettings()->queuePriority,
                max(0, $delay),
            );

            return true;
        } catch (Throwable $e) {
            $this->_transition($receiptId, $previousStatus, [
                'scheduledAt' => $previousStatus === 'scheduled' ? $row['scheduledAt'] : null,
                'error' => 'Queue publication failed; recovery will retry.',
            ]);
            throw new ServerErrorHttpException('Unable to schedule payment webhook processing.', 0, $e);
        }
    }

    private function _row(int $receiptId): ?array
    {
        $row = Craft::$app->getDb()->useMaster(fn() => (new Query())->from(Table::FORMIE_WEBHOOK_RECEIPTS)->where(['id' => $receiptId])->one());

        return $row ?: null;
    }

    private function _receipt(array $row): PaymentWebhookReceipt
    {
        return new PaymentWebhookReceipt(
            (int)$row['id'],
            (int)$row['integrationId'],
            (string)$row['integrationUid'],
            (string)$row['accountFingerprint'],
            (string)$row['environment'],
            (string)$row['eventId'],
            (string)$row['eventType'],
            $row['resourceType'] ?: null,
            $row['resourceReference'] ?: null,
            $row['providerCreatedAt'] !== null ? (int)$row['providerCreatedAt'] : null,
            Json::decode($this->_decrypt($row['payload'])),
            (string)$row['status'],
            (int)$row['attempts'],
        );
    }

    private function _transition(int $receiptId, string $status, array $values = []): void
    {
        $history = Json::decodeIfJson((string)(new Query())->select('history')->from(Table::FORMIE_WEBHOOK_RECEIPTS)->where(['id' => $receiptId])->scalar());
        $history = is_array($history) ? $history : [];
        $history[] = ['status' => $status, 'at' => gmdate('c')];
        Craft::$app->getDb()->createCommand()->update(Table::FORMIE_WEBHOOK_RECEIPTS, $values + [
            'status' => $status,
            'history' => Json::encode(array_slice($history, -100)),
        ], ['id' => $receiptId])->execute();
    }

    private function _trigger(Payment $integration, string $name, ?PaymentWebhookCommand $request = null, ?VerifiedWebhookBatch $verifiedWebhook = null, ?PaymentWebhookReceipt $receipt = null, ?Throwable $error = null): void
    {
        if (!$integration->hasEventHandlers($name)) {
            return;
        }

        $integration->trigger($name, new PaymentWebhookLifecycleEvent([
            'integration' => $integration,
            'request' => $request,
            'verifiedWebhook' => $verifiedWebhook,
            'receipt' => $receipt,
            'error' => $error,
        ]));
    }

    private function _display(VerifiedWebhook $event): string
    {
        return Html::encode(Json::encode([
            'eventId' => mb_substr($event->providerEventId, 0, 255),
            'eventType' => mb_substr($event->eventType, 0, 255),
            'resourceType' => $event->resourceType ? mb_substr($event->resourceType, 0, 255) : null,
            'resourceReference' => $event->resourceReference ? mb_substr($event->resourceReference, 0, 255) : null,
            'payload' => '[redacted]',
        ]));
    }

    private function _encrypt(string $value): string
    {
        $cipher = Craft::$app->getSecurity()->encryptByKey($value, Formie::$plugin->getSettings()->getSecurityKey());

        if (!is_string($cipher)) {
            throw new RuntimeException('Unable to encrypt webhook evidence.');
        }

        return base64_encode($cipher);
    }

    private function _decrypt(string $value): string
    {
        $cipher = base64_decode($value, true);

        if (!is_string($cipher)) {
            throw new RuntimeException('Webhook evidence encoding is invalid.');
        }

        $plain = Craft::$app->getSecurity()->decryptByKey($cipher, Formie::$plugin->getSettings()->getSecurityKey());

        if (!is_string($plain)) {
            throw new RuntimeException('Webhook evidence cannot be decrypted.');
        }

        return $plain;
    }
}
