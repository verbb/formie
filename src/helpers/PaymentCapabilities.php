<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\enums\PaymentCapabilityPurpose;

use Craft;
use craft\db\Query;
use craft\helpers\Json;

use InvalidArgumentException;
use RuntimeException;

final class PaymentCapabilities
{
    // Static Methods
    // =========================================================================

    public static function issue(PaymentCapabilityPurpose $purpose, int $resourceId, array $scope, int $ttl): string
    {
        if ($resourceId <= 0) {
            throw new InvalidArgumentException('Invalid payment capability.');
        }
        $purpose = $purpose->value;
        $db = Craft::$app->getDb();
        $scopeJson = Json::encode($scope);
        $lock = 'formie.payment-capability.' . hash('sha256', $purpose . '|' . $resourceId . '|' . $scopeJson);
        $mutex = Craft::$app->getMutex();

        if (!$mutex->acquire($lock, 5)) {
            throw new RuntimeException('Capability issuance is busy.');
        }

        try {
            $row = $ttl > 0 ? (new Query())->from(Table::FORMIE_PAYMENT_CAPABILITIES)->where(['purpose' => $purpose, 'resourceId' => $resourceId, 'scope' => $scopeJson, 'revokedAt' => null])->andWhere(['>', 'expiresAt', time()])->one() : null;

            if (!$row) {
                $row = ['purpose' => $purpose, 'resourceId' => $resourceId, 'scope' => $scopeJson,
                    'expiresAt' => time() + $ttl, 'tokenHash' => bin2hex(random_bytes(32))];
                $db->createCommand()->insert(Table::FORMIE_PAYMENT_CAPABILITIES, $row)->execute();
                $row['id'] = (int)$db->getLastInsertID();
                $token = self::_token($row);
                $db->createCommand()->update(Table::FORMIE_PAYMENT_CAPABILITIES, ['tokenHash' => self::_hash($token)], ['id' => $row['id']])->execute();
                return $token;
            }
            // Derive the same bearer without storing it, so provider return URLs remain stable on retry.
            return self::_token($row);
        } finally {
            $mutex->release($lock);
        }
    }

    public static function resolve(string $token, PaymentCapabilityPurpose $purpose): ?array
    {
        $purpose = $purpose->value;
        $row = Craft::$app->getDb()->useMaster(fn() => (new Query())->from(Table::FORMIE_PAYMENT_CAPABILITIES)->where(['tokenHash' => self::_hash($token), 'purpose' => $purpose, 'revokedAt' => null])->andWhere(['>', 'expiresAt', time()])->one());

        if (!$row) {
            return null;
        }
        $row['scope'] = Json::decodeIfJson($row['scope']);
        return $row;
    }

    public static function revoke(PaymentCapabilityPurpose $purpose, int $resourceId): void
    {
        Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PAYMENT_CAPABILITIES, ['revokedAt' => time()], ['purpose' => $purpose->value, 'resourceId' => $resourceId])->execute();
    }

    private static function _token(array $row): string
    {
        return hash_hmac('sha256', 'payment-capability|' . $row['id'] . '|' . $row['purpose'] . '|' . $row['resourceId'] . '|' . $row['expiresAt'], Formie::$plugin->getSettings()->getSecurityKey());
    }

    private static function _hash(string $token): string
    {
        return hash_hmac('sha256', $token, Formie::$plugin->getSettings()->getSecurityKey());
    }
}
