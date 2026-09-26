<?php
namespace verbb\formie\models\payments;

use verbb\formie\enums\PaymentResumeMode;
use verbb\formie\helpers\PaymentAccess;

use yii\web\NotFoundHttpException;

final class PaymentStatusCommand
{
    // Public Methods
    // =========================================================================

    public function __construct(public readonly string $token)
    {
    }

    public function resolve(): array
    {
        return PaymentAccess::resolveStatusToken($this->token) ?? throw new NotFoundHttpException('Payment not found.');
    }

    public function mode(): PaymentResumeMode
    {
        return PaymentResumeMode::from($this->resolve()['purpose']);
    }
}
