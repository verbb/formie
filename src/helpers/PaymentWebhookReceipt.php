<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\base\Payment;

use Craft;
use craft\db\Query;
use craft\helpers\Html;
use craft\helpers\Json;

use yii\web\ForbiddenHttpException;

use RuntimeException;
use Throwable;

final class PaymentWebhookReceipt
{
    // Static Methods
    // =========================================================================

    /** Called only after the adapter authenticates the exact request bytes. */
    public static function process(Payment $integration, string $environment, string $eventId, string $body, array $headers, callable $handler, ?string $eventFingerprint = null): void
    {
        if ($eventId === '' || !$integration->id) {
            throw new RuntimeException('A verified webhook requires a durable event identity.');
        }
        if (Craft::$app->getDb()->getTransaction()?->getIsActive()) {
            throw new RuntimeException('Webhook evidence must commit before domain processing.');
        }
        $eventHash = hash('sha256', $eventFingerprint ?? $body);
        $identity = hash('sha256', get_class($integration) . '|' . $integration->id . '|' . $environment . '|' . $eventId);
        $mutex = Craft::$app->getMutex();
        $lock = 'formie.webhook.' . $identity;
        if (!$mutex->acquire($lock, 10)) {
            throw new RuntimeException('Webhook processing is already in progress.');
        }
        $db = Craft::$app->getDb();
        $id = null;
        try {
            $row = $db->useMaster(fn() => (new Query())->from(Table::FORMIE_WEBHOOK_RECEIPTS)->where(['identity' => $identity])->one());
            if ($row && !hash_equals($row['eventHash'], $eventHash)) {
                throw new RuntimeException('Webhook identity was reused with different evidence.');
            }
            if ($row && in_array($row['status'], ['processed', 'ignored'], true)) {
                return;
            }
            if (!$row) {
                $db->createCommand()->insert(Table::FORMIE_WEBHOOK_RECEIPTS, [
                    'identity' => $identity, 'integrationId' => $integration->id, 'environment' => $environment,
                    'eventId' => $eventId, 'bodyHash' => hash('sha256', $body), 'eventHash' => $eventHash,
                    'history' => Json::encode([['status' => 'received', 'at' => gmdate('c')], ['status' => 'verified', 'at' => gmdate('c')]]),
                    'body' => self::_encrypt($body), 'headers' => self::_encrypt(Json::encode($headers)),
                    'display' => self::display($body), 'status' => 'verified', 'attempts' => 0,
                    'receivedAt' => gmdate('Y-m-d H:i:s'),
                ])->execute();
                $id = (int)$db->getLastInsertID();
                $row = ['attempts' => 0];
            } else {
                $id = (int)$row['id'];
            }
            // Evidence is committed before any domain handling. Interrupted processing is retryable;
            // domain locks, provider resource identities and operation receipts gate side effects.
            self::_transition($id, 'processing', ['attempts' => (int)$row['attempts'] + 1, 'error' => null]);
            $handled = Formie::$plugin->getPayments()->observeProvider($handler);
            self::_transition($id, $handled === false ? 'ignored' : 'processed', ['processedAt' => gmdate('Y-m-d H:i:s')]);
        } catch (Throwable $e) {
            if ($id) {
                // Do not copy arbitrary provider exceptions or secrets into support projections.
                self::_transition($id, 'failed');
                self::_transition($id, 'reconciliation', ['error' => 'Processing failed; retry the authenticated event or reconcile its resource.']);
            }
            throw $e;
        } finally {
            $mutex->release($lock);
        }
    }

    public static function display(string $body): string
    {
        $data = Json::decodeIfJson($body);
        $projection = [];
        foreach (['id', 'type', 'created', 'livemode'] as $key) {
            if (isset($data[$key]) && is_scalar($data[$key])) {
                $projection[$key] = is_string($data[$key]) ? mb_substr($data[$key], 0, 255) : $data[$key];
            }
        }
        $projection['payload'] = '[redacted]';
        return Html::encode(Json::encode($projection));
    }

    /** Exact evidence is available only through trusted, permission-checked diagnostics. */
    public static function evidence(int $id): string
    {
        if (!Craft::$app->getRequest()->getIsConsoleRequest() && !Formie::$plugin->getPermissions()->canAccessIntegrations(Craft::$app->getUser()->getIdentity())) {
            throw new ForbiddenHttpException('Payment evidence is restricted.');
        }
        $body = (new Query())->from(Table::FORMIE_WEBHOOK_RECEIPTS)->select('body')->where(['id' => $id])->scalar();
        if (!$body) {
            throw new RuntimeException('Webhook receipt not found.');
        }
        $plain = Craft::$app->getSecurity()->decryptByKey(base64_decode($body, true), Formie::$plugin->getSettings()->getSecurityKey());
        if (!is_string($plain)) {
            throw new RuntimeException('Webhook evidence cannot be decrypted.');
        }
        return $plain;
    }

    private static function _transition(int $id, string $status, array $values = []): void
    {
        $history = Json::decode((new Query())->select('history')->from(Table::FORMIE_WEBHOOK_RECEIPTS)->where(['id' => $id])->scalar());
        $history[] = ['status' => $status, 'at' => gmdate('c')];
        Craft::$app->getDb()->createCommand()->update(Table::FORMIE_WEBHOOK_RECEIPTS, $values + ['status' => $status, 'history' => Json::encode(array_slice($history, -100))], ['id' => $id])->execute();
    }

    private static function _encrypt(string $value): string
    {
        $cipher = Craft::$app->getSecurity()->encryptByKey($value, Formie::$plugin->getSettings()->getSecurityKey());
        if (!is_string($cipher)) {
            throw new RuntimeException('Unable to encrypt webhook evidence.');
        }
        return base64_encode($cipher);
    }
}
