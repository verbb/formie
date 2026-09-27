<?php
namespace verbb\formie\models\payments;

use verbb\formie\Formie;
use verbb\formie\helpers\PaymentAccess;
use verbb\formie\models\Payment;

use yii\web\NotFoundHttpException;

final class PaymentReturnCommand
{
    // Public Methods
    // =========================================================================

    public function __construct(public readonly string $token)
    {
    }

    public function payment(): Payment
    {
        $scope = PaymentAccess::resolveReturnToken($this->token);
        $payment = $scope ? Formie::$plugin->getPayments()->getPaymentById((int)$scope['paymentId']) : null;

        if (!$payment) {
            throw new NotFoundHttpException('Payment not found.');
        }

        return $payment;
    }
}
