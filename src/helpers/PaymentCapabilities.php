<?php
namespace verbb\formie\helpers;

use verbb\formie\content\ContentEncryption;
use verbb\formie\enums\PaymentCapabilityPurpose;

use Craft;
use craft\db\Query;
use craft\helpers\Json;

use InvalidArgumentException;

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
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $db->createCommand()->insert(Table::FORMIE_PAYMENT_CAPABILITIES, [
            'purpose' => $purpose,
            'resourceId' => $resourceId,
            'scope' => Json::encode($scope),
            'expiresAt' => time() + $ttl,
            'tokenHash' => self::_hash($token),
        ])->execute();

        return $token;
    }

    public static function resolve(string $token, PaymentCapabilityPurpose $purpose): ?array
    {
        $purpose = $purpose->value;
        $hashes = [self::_hash($token)];

        foreach (ContentEncryption::legacyKeys() as $key) {
            $hashes[] = hash_hmac('sha256', $token, $key);
        }

        $row = Craft::$app->getDb()->useMaster(fn() => (new Query())->from(Table::FORMIE_PAYMENT_CAPABILITIES)->where(['tokenHash' => array_unique($hashes), 'purpose' => $purpose, 'revokedAt' => null])->andWhere(['>', 'expiresAt', time()])->one());

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

    private static function _hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
