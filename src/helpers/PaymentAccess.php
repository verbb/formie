<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\enums\PaymentResumeMode;
use verbb\formie\fields\Payment;
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

    public static function issueStatusToken(PaymentModel $payment, ?int $issuedAt = null, PaymentResumeMode $mode = PaymentResumeMode::STATUS): ?string
    {
        if (!$payment->id || !$payment->uid) {
            return null;
        }
        return PaymentCapabilities::issue($mode->value, $payment->id, ['paymentUid' => $payment->uid,
            'integrationId' => $payment->integrationId, 'submissionId' => $payment->submissionId],
            (($issuedAt ?? time()) + self::STATUS_TOKEN_TTL_SECONDS - time()));
    }

    public static function resolveStatusToken(?string $token): ?array
    {
        if (!$token) {
            return null;
        }
        foreach (['status', 'reconcile'] as $purpose) {
            if ($row = PaymentCapabilities::resolve($token, $purpose)) {
                $payment = Formie::$plugin->getPayments()->getPaymentById((int)$row['resourceId']);
                $scope = $row['scope'];
                if ($payment && $payment->uid === ($scope['paymentUid'] ?? null)
                    && $payment->integrationId === ($scope['integrationId'] ?? null)
                    && $payment->submissionId === ($scope['submissionId'] ?? null)) {
                    return ['paymentUid' => $payment->uid, 'paymentId' => $payment->id, 'purpose' => $purpose, 'expiresAt' => $row['expiresAt']];
                }
            }
        }
        return null;
    }

    public static function issueProviderSessionToken(string $provider, int $integrationId, string $integrationHandle, ?int $issuedAt = null, ?int $formId = null, ?int $fieldId = null, ?int $siteId = null): ?string
    {
        if (!$formId || !$fieldId || !$siteId || $integrationId <= 0) { return null; }
        return PaymentCapabilities::issue('session', $integrationId, ['provider' => $provider, 'integrationHandle' => $integrationHandle,
            'formId' => $formId, 'fieldId' => $fieldId, 'siteId' => $siteId], (($issuedAt ?? time()) + self::PROVIDER_SESSION_TOKEN_TTL_SECONDS - time()));
    }

    public static function resolveProviderSessionToken(?string $token, string $provider): ?array
    {
        $row = $token ? PaymentCapabilities::resolve($token, 'session') : null;
        if (!$row || ($row['scope']['provider'] ?? null) !== $provider) { return null; }
        $scope = $row['scope'];
        $form = Formie::$plugin->getForms()->getFormById((int)$scope['formId'], (int)$scope['siteId']);
        if (!$form || !$form->enabled) { return null; }
        foreach ($form->getFields() as $field) {
            if ((int)$field->id === (int)$scope['fieldId'] && $field instanceof Payment && !$field->getIsDisabled()
                && (int)$field->getPaymentIntegration()?->id === (int)$row['resourceId']) {
                return $scope + ['integrationId' => (int)$row['resourceId'], 'expiresAt' => (int)$row['expiresAt']];
            }
        }
        return null;
    }
}
