<?php
namespace verbb\formie\models\payments;

use verbb\formie\helpers\PaymentAccess;

use yii\web\NotFoundHttpException;

final class PaymentReturnCommand
{
    // Public Methods
    // =========================================================================

    public function __construct(public readonly string $token)
    {
    }

    public function statusToken(): string
    {
        if (!PaymentAccess::resolveStatusToken($this->token)) {
            throw new NotFoundHttpException('Payment not found.');
        }
        return $this->token;
    }
}
