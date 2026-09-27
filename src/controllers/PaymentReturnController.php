<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\helpers\PaymentAccess;
use verbb\formie\models\payments\PaymentReturnCommand;

use craft\helpers\UrlHelper;
use craft\web\Controller;

use yii\web\NotFoundHttpException;
use yii\web\Response;

use Throwable;

class PaymentReturnController extends Controller
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = ['index'];


    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        $token = (string)$this->request->getRequiredParam('returnToken');
        $command = new PaymentReturnCommand($token);
        $payment = $command->payment();

        try {
            $payment = Formie::$plugin->getPayments()->refreshIfDue($payment);
        } catch (Throwable) {
            // A browser return never decides the financial outcome. Continue to
            // the read-only status surface while server reconciliation retries.
        }
        $statusToken = PaymentAccess::issueStatusToken($payment);

        if (!$statusToken) {
            throw new NotFoundHttpException('Payment not found.');
        }

        return $this->redirect(UrlHelper::actionUrl('formie/payment-status/status', ['statusToken' => $statusToken]));
    }
}
