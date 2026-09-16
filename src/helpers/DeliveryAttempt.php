<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\errors\DeliveryOutcomeUnknownException;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\workflow\WorkflowContext;

use Craft;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;

use DateTime;
use RuntimeException;
use Throwable;

use GuzzleHttp\Exception\RequestException;
use Stripe\Exception\ApiErrorException;

/**
 * Durable identity for one intended external operation. Only hashes, timestamps
 * and provider references are stored; request and response bodies stay out of meta.
 */
class DeliveryAttempt
{
    // Static Methods
    // =========================================================================

    public static function workflowIdentity(): ?string
    {
        $context = WorkflowContext::current();
        if (!$context) {
            return null;
        }
        if ($context->request->requestToken) {
            return 'workflow:' . $context->request->requestToken;
        }
        if ($context->request->processMode !== SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING) {
            return 'completion';
        }
        return $context->taskState['delivery.identity'] ??= StringHelper::UUID();
    }

    /** Bind a provider resource to one submission/field before consuming it. */
    public static function claimResource(int $submissionId, string $provider, string $resource, int $fieldId): void
    {
        $operation = 'claim:' . $provider;
        $where = [
            'stage' => 'delivery.' . substr(hash('sha256', $operation), 0, 48),
            'idempotencyKey' => hash('sha256', $resource),
        ];
        $mutex = Craft::$app->getMutex();
        $lock = 'formie.resource.' . hash('sha256', Json::encode($where));
        if (!$mutex->acquire($lock, 10)) {
            throw new RuntimeException('Payment resource is already in use. Retry later.');
        }
        try {
            $owner = (new Query())->select('submissionId')->from(Table::FORMIE_SUBMISSION_WORKFLOW)->where($where)->scalar();
            if ($owner !== false && (int)$owner !== $submissionId) {
                throw new RuntimeException('Payment resource belongs to another submission.');
            }
            (new self($submissionId, $operation, $resource))->execute(['fieldId' => $fieldId], fn() => true);
        } finally {
            $mutex->release($lock);
        }
    }


    // Public Methods
    // =========================================================================

    public function __construct(
        private int $submissionId,
        private string $operation,
        private string $identity,
        private ?string $providerKey = null,
    ) {
        if ($submissionId <= 0) {
            throw new RuntimeException('Save the submission before starting delivery.');
        }
    }

    public function getMetadata(): ?array
    {
        $meta = Craft::$app->getDb()->useMaster(fn() => (new Query())->select('meta')->from(Table::FORMIE_SUBMISSION_WORKFLOW)->where([
            'submissionId' => $this->submissionId,
            'stage' => 'delivery.' . substr(hash('sha256', $this->operation), 0, 48),
            'idempotencyKey' => hash('sha256', $this->identity),
        ])->scalar());

        return $meta === false ? null : Json::decode($meta);
    }

    /**
     * $send receives a stable provider idempotency key. A positive retry window
     * requires provider duplicate protection or a guarded local enqueue.
     * $reconcile retrieves an already accepted resource by its saved reference.
     */
    public function execute(
        array $payload,
        callable $send,
        int $retryWindow = 0,
        ?callable $reference = null,
        ?callable $reconcile = null,
    ): mixed {
        if (Craft::$app->getDb()->getTransaction()?->getIsActive()) {
            throw new RuntimeException('Commit the submission transaction before starting external delivery.');
        }

        $where = [
            'submissionId' => $this->submissionId,
            'stage' => 'delivery.' . substr(hash('sha256', $this->operation), 0, 48),
            'idempotencyKey' => hash('sha256', $this->identity),
        ];
        $lock = 'formie.delivery.' . hash('sha256', Json::encode($where));
        $mutex = Craft::$app->getMutex();

        if (!$mutex->acquire($lock, 10)) {
            throw new RuntimeException('Delivery is already in progress. Retry later.');
        }

        try {
            $hash = hash_hmac('sha256', Json::encode($this->_canonicalize($payload)), Formie::$plugin->getSettings()->getSecurityKey());
            $row = (new Query())->from(Table::FORMIE_SUBMISSION_WORKFLOW)->where($where)->one();
            $meta = $row ? Json::decode($row['meta']) : [
                'requestKey' => $this->providerKey ?? StringHelper::UUID(),
                'payloadHash' => $hash,
                'startedAt' => time(),
                'state' => 'ready',
            ];

            if (!hash_equals($meta['payloadHash'], $hash) && $meta['state'] === 'rejected') {
                // A confirmed rejection permits a corrected, new operation.
                $meta = ['requestKey' => StringHelper::UUID(), 'payloadHash' => $hash, 'startedAt' => time(), 'state' => 'ready'];
            }
            if (!hash_equals($meta['payloadHash'], $hash)) {
                throw new RuntimeException('Delivery parameters changed. Check the previous outcome before starting a new operation.');
            }

            if ($reconcile && !empty($meta['reference'])) {
                try {
                    $result = $reconcile($meta['reference']);
                } catch (Throwable $e) {
                    throw new DeliveryOutcomeUnknownException('Unable to confirm the existing delivery. Check the destination before starting another operation.', 0, $e);
                }
                $meta['state'] = 'completed';
                $this->_save($where, $meta);
                return $result;
            }
            if ($meta['state'] === 'completed') {
                return true;
            }

            if (in_array($meta['state'], ['sending', 'unknown'], true)
                && ($retryWindow <= 0 || time() - $meta['startedAt'] >= $retryWindow)) {
                throw new DeliveryOutcomeUnknownException('Delivery outcome unknown. Check the destination before explicitly running this operation again. Automatic retry has been stopped.');
            }

            $meta['state'] = 'sending';
            $this->_save($where, $meta);

            try {
                $result = $send($meta['requestKey']);
                $meta['state'] = $result === false ? 'failed' : 'completed';
                if ($reference && $result !== false) {
                    $meta['reference'] = (string)$reference($result);
                    if ($meta['reference'] === '') {
                        throw new RuntimeException('The provider did not return a delivery reference.');
                    }
                }
                $this->_save($where, $meta);
                return $result;
            } catch (Throwable $e) {
                // A timeout, worker failure or failed local write is not evidence
                // that the provider rejected the request.
                $meta['state'] = $retryWindow > 0 && $this->_isDefiniteRejection($e) ? 'rejected' : 'unknown';
                $this->_save($where, $meta);
                if ($meta['state'] === 'rejected') {
                    throw $e;
                }
                throw new DeliveryOutcomeUnknownException('Delivery outcome unknown. Check the destination before starting another operation.', 0, $e);
            }
        } finally {
            $mutex->release($lock);
        }
    }


    // Private Methods
    // =========================================================================

    private function _isDefiniteRejection(Throwable $error): bool
    {
        $status = null;
        if ($error instanceof RequestException) {
            $status = $error->getResponse()?->getStatusCode();
        } elseif ($error instanceof ApiErrorException) {
            $status = $error->getHttpStatus();
        }
        return $status !== null && $status >= 400 && $status < 500 && !in_array($status, [408, 409, 429], true);
    }

    private function _save(array $where, array $meta): void
    {
        $now = Db::prepareDateForDb(new DateTime());
        $values = [
            'isDispatched' => $meta['state'] === 'completed',
            'meta' => Json::encode($meta),
            'dateDispatched' => $now,
            'dateUpdated' => $now,
        ];
        Craft::$app->getDb()->createCommand()->upsert(Table::FORMIE_SUBMISSION_WORKFLOW,
            array_merge($where, $values, ['dateCreated' => $now, 'uid' => StringHelper::UUID()]),
            $values,
        )->execute();
    }

    private function _canonicalize(array $value): array
    {
        if (!array_is_list($value)) {
            ksort($value);
        }
        foreach ($value as &$item) {
            if (is_array($item)) {
                $item = $this->_canonicalize($item);
            }
        }
        unset($item);
        return $value;
    }
}
