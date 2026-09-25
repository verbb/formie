<?php
namespace verbb\formie\services;

use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\helpers\Table;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\models\SubmissionOutcome;

use Craft;
use craft\db\Query;
use craft\helpers\Json;

use yii\base\Component;

use RuntimeException;

/** Serializes mutations and retains encrypted retry results for seven days. */
class SubmissionOperations extends Component
{
    // Constants
    // =========================================================================

    public const RETENTION_SECONDS = 604800;
    public const MAX_OUTCOME_BYTES = 65536;


    // Public Methods
    // =========================================================================

    public function execute(SubmissionCommand $command, callable $execute): SubmissionOutcome
    {
        $db = Craft::$app->getDb();
        $mutex = Craft::$app->getMutex();
        $hash = $this->_hash($command);
        $keys = [];

        try {
            // Lock order is invariant: operation first, then the real resource.
            if ($hash !== null) {
                $this->_lock('formie.operation.' . $hash, $keys);
                $receipt = (new Query())->from(Table::FORMIE_SUBMISSION_OPERATIONS)->where(['operationHash' => $hash])->one();
                if ($receipt) {
                    if (!hash_equals((string)$receipt['fingerprint'], (string)$command->payloadFingerprint)) {
                        return $this->_conflict($command, 'operationInputChanged');
                    }
                    if ($receipt['state'] === 'completed') {
                        return $this->_decode($receipt['outcome']);
                    }
                    // A crashed operation may have reached a provider. Never repeat it blindly.
                    return $this->_conflict($command, 'operationRequiresReconciliation');
                }
            }

            $requestHash = $command->isInteractive() && $command->requestToken
                ? hash('sha256', $command->authority->scope . '|' . $command->requestToken) : null;
            if ($requestHash !== null) {
                $this->_lock('formie.request.' . $requestHash, $keys);
                if ((new Query())->from(Table::FORMIE_SUBMISSION_OPERATIONS)->where(['requestHash' => $requestHash])->exists()) {
                    return $this->_conflict($command, 'requestAlreadyUsed');
                }
            }

            $resource = $command->submission->id
                ? 'submission:' . $command->submission->id
                : 'new:' . $command->form->id . ':' . $command->authority->scope;
            $this->_lock('formie.resource.' . hash('sha256', $resource), $keys);

            if ($command->submission->id) {
                $version = (new Query())->select('stateVersion')->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $command->submission->id])->scalar();
                if ($version === false || $command->expectedVersion === null || (int)$version !== $command->expectedVersion) {
                    return $this->_conflict($command, 'staleVersion', $version === false ? null : (int)$version);
                }
            } elseif ($command->expectedVersion !== null && $command->expectedVersion !== 0) {
                return $this->_conflict($command, 'staleVersion', 0);
            }

            if ($hash !== null) {
                $now = gmdate('Y-m-d H:i:s');
                $db->createCommand()->insert(Table::FORMIE_SUBMISSION_OPERATIONS, [
                    'operationHash' => $hash,
                    'requestHash' => $requestHash,
                    'fingerprint' => $command->payloadFingerprint,
                    'formId' => $command->form->id,
                    'submissionId' => $command->submission->id,
                    'operation' => $command->operation->value,
                    'state' => 'processing',
                    'dateCreated' => $now,
                    'dateUpdated' => $now,
                    'expiresAt' => gmdate('Y-m-d H:i:s', time() + self::RETENTION_SECONDS),
                ])->execute();
            }

            $outcome = $execute();
            if (!$outcome instanceof SubmissionOutcome) {
                throw new RuntimeException('Submission execution must produce a domain outcome.');
            }

            if ($hash !== null) {
                if ($outcome->type === SubmissionOutcomeType::VALIDATION_FAILED) {
                    // Validation did not mutate durable state; corrected input may reuse the issued token.
                    $db->createCommand()->delete(Table::FORMIE_SUBMISSION_OPERATIONS, ['operationHash' => $hash])->execute();
                } else {
                    $db->createCommand()->update(Table::FORMIE_SUBMISSION_OPERATIONS, [
                        'state' => 'completed', 'outcome' => $this->_encode($outcome),
                        'submissionId' => $outcome->submissionId, 'dateUpdated' => gmdate('Y-m-d H:i:s'),
                    ], ['operationHash' => $hash])->execute();
                }
            }

            return $outcome;
        } finally {
            foreach (array_reverse($keys) as $key) {
                $mutex->release($key);
            }
        }
    }

    public function bindSubmission(SubmissionCommand $command, int $submissionId): void
    {
        if (($hash = $this->_hash($command)) !== null) {
            Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SUBMISSION_OPERATIONS, [
                'submissionId' => $submissionId,
            ], ['operationHash' => $hash, 'state' => 'processing'])->execute();
        }
    }

    public function prune(): int
    {
        // Processing receipts are tombstones until their retry horizon expires too.
        return Craft::$app->getDb()->createCommand()->delete(Table::FORMIE_SUBMISSION_OPERATIONS, ['<', 'expiresAt', gmdate('Y-m-d H:i:s')])->execute();
    }

    public function fingerprint(array $input): string
    {
        $normalize = function (mixed $value) use (&$normalize): mixed {
            if (!is_array($value)) {
                return $value;
            }
            if (!array_is_list($value)) {
                ksort($value);
            }
            return array_map($normalize, $value);
        };
        return hash_hmac('sha256', Json::encode($normalize($input)), \verbb\formie\Formie::$plugin->getSettings()->getSecurityKey());
    }


    // Private Methods
    // =========================================================================

    private function _hash(SubmissionCommand $command): ?string
    {
        if (!$command->operationId) {
            if ($command->isInteractive()) {
                throw new RuntimeException('Interactive operations require an operation ID.');
            }
            return null;
        }
        if (!$command->payloadFingerprint) {
            throw new RuntimeException('Replayable operations require an input fingerprint.');
        }
        return hash('sha256', $command->form->id . '|' . $command->authority->scope . '|' . $command->operationId);
    }

    private function _lock(string $key, array &$keys): void
    {
        if (!Craft::$app->getMutex()->acquire($key, 10)) {
            throw new RuntimeException('Submission processing is already in progress.');
        }
        $keys[] = $key;
    }

    private function _conflict(SubmissionCommand $command, string $reason, ?int $version = null): SubmissionOutcome
    {
        return new SubmissionOutcome(SubmissionOutcomeType::STATE_CONFLICT, $command->submission->id, $command->submission->uid, $version, data: ['reason' => $reason]);
    }

    private function _encode(SubmissionOutcome $outcome): string
    {
        $data = get_object_vars($outcome);
        $data['type'] = $outcome->type->value;
        $json = Json::encode($data);
        if (strlen($json) > self::MAX_OUTCOME_BYTES) {
            throw new RuntimeException('Submission outcome exceeded the receipt size limit.');
        }
        return base64_encode(Craft::$app->getSecurity()->encryptByKey($json, Craft::$app->getConfig()->getGeneral()->securityKey));
    }

    private function _decode(string $encrypted): SubmissionOutcome
    {
        $json = Craft::$app->getSecurity()->decryptByKey(base64_decode($encrypted, true), Craft::$app->getConfig()->getGeneral()->securityKey);
        if ($json === false) {
            throw new RuntimeException('Unable to decrypt the operation receipt.');
        }
        $data = Json::decode($json);
        $data['type'] = SubmissionOutcomeType::from($data['type']);
        return new SubmissionOutcome(...$data);
    }
}
