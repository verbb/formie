<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\models\Payment as PaymentModel;

use Craft;
use craft\helpers\Json;

final class PaymentAccess
{
    // Constants
    // =========================================================================

    private const STATUS_TOKEN_TTL_SECONDS = 86400;
    private const PROVIDER_SESSION_TOKEN_TTL_SECONDS = 1800;


    // Static Methods
    // =========================================================================

    public static function issueStatusToken(PaymentModel $payment, ?int $issuedAt = null): ?string
    {
        $paymentUid = trim((string)($payment->uid ?? ''));
        $paymentId = (int)($payment->id ?? 0);

        if ($paymentUid === '' || $paymentId <= 0) {
            return null;
        }

        $issuedAt ??= time();
        $payload = Json::encode([
            'paymentUid' => $paymentUid,
            'paymentId' => $paymentId,
            'issuedAt' => $issuedAt,
            'expiresAt' => $issuedAt + self::STATUS_TOKEN_TTL_SECONDS,
        ]);

        $key = Formie::$plugin->getSettings()->getSecurityKey();
        $encrypted = Craft::$app->getSecurity()->encryptByKey($payload, $key);

        if (!is_string($encrypted) || $encrypted === '') {
            return null;
        }

        return base64_encode($encrypted);
    }

    public static function resolveStatusToken(?string $token): ?array
    {
        if (!is_string($token) || trim($token) === '') {
            return null;
        }

        $decoded = base64_decode(trim($token), true);

        if (!is_string($decoded) || $decoded === '') {
            return null;
        }

        $key = Formie::$plugin->getSettings()->getSecurityKey();
        $decrypted = Craft::$app->getSecurity()->decryptByKey($decoded, $key);

        if (!is_string($decrypted) || $decrypted === '') {
            return null;
        }

        $payload = Json::decodeIfJson($decrypted);

        if (!is_array($payload)) {
            return null;
        }

        $paymentUid = isset($payload['paymentUid']) && is_string($payload['paymentUid']) ? trim($payload['paymentUid']) : '';
        $paymentId = isset($payload['paymentId']) ? (int)$payload['paymentId'] : 0;
        $expiresAt = isset($payload['expiresAt']) ? (int)$payload['expiresAt'] : 0;

        if ($paymentUid === '' || $paymentId <= 0 || $expiresAt <= time()) {
            return null;
        }

        return [
            'paymentUid' => $paymentUid,
            'paymentId' => $paymentId,
            'expiresAt' => $expiresAt,
        ];
    }

    public static function issueProviderSessionToken(string $provider, int $integrationId, string $integrationHandle, ?int $issuedAt = null): ?string
    {
        $provider = trim($provider);
        $integrationHandle = trim($integrationHandle);

        if ($provider === '' || $integrationId <= 0 || $integrationHandle === '') {
            return null;
        }

        $issuedAt ??= time();
        $payload = Json::encode([
            'provider' => $provider,
            'integrationId' => $integrationId,
            'integrationHandle' => $integrationHandle,
            'issuedAt' => $issuedAt,
            'expiresAt' => $issuedAt + self::PROVIDER_SESSION_TOKEN_TTL_SECONDS,
        ]);

        $key = Formie::$plugin->getSettings()->getSecurityKey();
        $encrypted = Craft::$app->getSecurity()->encryptByKey($payload, $key);

        if (!is_string($encrypted) || $encrypted === '') {
            return null;
        }

        return base64_encode($encrypted);
    }

    public static function resolveProviderSessionToken(?string $token, string $provider): ?array
    {
        if (!is_string($token) || trim($token) === '') {
            return null;
        }

        $decoded = base64_decode(trim($token), true);

        if (!is_string($decoded) || $decoded === '') {
            return null;
        }

        $key = Formie::$plugin->getSettings()->getSecurityKey();
        $decrypted = Craft::$app->getSecurity()->decryptByKey($decoded, $key);

        if (!is_string($decrypted) || $decrypted === '') {
            return null;
        }

        $payload = Json::decodeIfJson($decrypted);

        if (!is_array($payload)) {
            return null;
        }

        $tokenProvider = isset($payload['provider']) && is_string($payload['provider']) ? trim($payload['provider']) : '';
        $integrationId = isset($payload['integrationId']) ? (int)$payload['integrationId'] : 0;
        $integrationHandle = isset($payload['integrationHandle']) && is_string($payload['integrationHandle']) ? trim($payload['integrationHandle']) : '';
        $expiresAt = isset($payload['expiresAt']) ? (int)$payload['expiresAt'] : 0;

        if ($tokenProvider !== trim($provider) || $integrationId <= 0 || $integrationHandle === '' || $expiresAt <= time()) {
            return null;
        }

        return [
            'provider' => $tokenProvider,
            'integrationId' => $integrationId,
            'integrationHandle' => $integrationHandle,
            'expiresAt' => $expiresAt,
        ];
    }
}
