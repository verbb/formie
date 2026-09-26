<?php
namespace verbb\formie\controllers;

use verbb\formie\helpers\PaymentAccess;
use verbb\formie\models\payments\PaymentReturnCommand;

use craft\helpers\UrlHelper;
use craft\web\Controller;

use yii\web\NotFoundHttpException;
use yii\web\Response;

class PaymentReturnController extends Controller
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = ['index'];


    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        $token = (string)$this->request->getRequiredParam('statusToken');
        $command = new PaymentReturnCommand($token);
        $token = $command->statusToken();
        // Provider navigation is a hint; only the capability-scoped status policy reconciles.
        return $this->redirect(UrlHelper::actionUrl('formie/payment-status/status', ['statusToken' => $token]));
    }
}
