<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\base\Payment;

use Craft;
use craft\helpers\Json;
use craft\web\Controller;

use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class PaymentSubscriptionsController extends Controller
{
    // Properties
    // =========================================================================

    public $enableCsrfValidation = false;

    protected array|bool|int $allowAnonymous = ['cancel'];


    // Public Methods
    // =========================================================================

    public function actionCancel(): ?Response
    {
        $id = $this->request->getRequiredParam('id');
        $hash = $this->request->getRequiredParam('hash');
        $params = $this->request->getParam('params', []);

        $subscription = Formie::$plugin->getSubscriptions()->getSubscriptionById($id);

        if (!$subscription) {
            return $this->asFailure('Subscription not found: ' . $id);            
        }

        $hash = Craft::$app->getSecurity()->validateData($hash);

        if ($hash !== $subscription->reference) {
            return $this->asFailure('Invalid subscription: ' . $id);            
        }

        $result = $subscription->getIntegration()->cancelSubscription($subscription->reference, $params);
        
        if (!$result) {
            return $this->asFailure('Unable to cancel subscription: ' . $id);            
        }

        return $this->asSuccess(Craft::t('formie', 'Subscription cancelled.'), data: [
            'subscription' => $subscription,
        ]);
    }
}
