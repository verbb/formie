<?php
namespace verbb\formie\models\payments;

use verbb\formie\helpers\PaymentAccess;

use yii\web\ForbiddenHttpException;

final class PaymentSessionCommand
{
    // Public Methods
    // =========================================================================

    public function __construct(public readonly string $token, public readonly int $integrationId)
    {
    }

    public function authorize(string $provider): array
    {
        $scope = PaymentAccess::resolveProviderSessionToken($this->token, $provider);
        if (!$scope || $scope['integrationId'] !== $this->integrationId) {
            throw new ForbiddenHttpException('Invalid payment session authority.');
        }
        return $scope;
    }
}
