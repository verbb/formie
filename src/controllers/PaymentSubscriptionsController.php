<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\base\Payment;

use Craft;
use craft\helpers\Html;
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
            return $this->asFailure(Craft::t('formie', 'Subscription not found.'));
        }

        $hash = Craft::$app->getSecurity()->validateData($hash);

        if ($hash !== $subscription->reference) {
            return $this->asFailure(Craft::t('formie', 'Invalid subscription request.'));
        }

        if (!$this->request->getIsPost()) {
            return $this->asRaw($this->_renderCancelConfirmation((int)$id, (string)$this->request->getRequiredParam('hash'), $params));
        }

        $result = $subscription->getIntegration()->cancelSubscription($subscription->reference, $params);
        
        if (!$result) {
            return $this->asFailure(Craft::t('formie', 'Unable to cancel subscription.'));
        }

        return $this->asSuccess(Craft::t('formie', 'Subscription cancelled.'), data: [
            'subscription' => $subscription,
        ]);
    }

    private function _renderCancelConfirmation(int $id, string $hash, mixed $params): string
    {
        $action = Craft::$app->getUrlManager()->createUrl('actions/formie/payment-subscriptions/cancel');
        $html = '<!doctype html><html><head><meta charset="utf-8"><title>' . Html::encode(Craft::t('formie', 'Cancel subscription')) . '</title></head><body>';
        $html .= '<h1>' . Html::encode(Craft::t('formie', 'Cancel subscription')) . '</h1>';
        $html .= '<p>' . Html::encode(Craft::t('formie', 'Are you sure you want to cancel this subscription?')) . '</p>';
        $html .= Html::beginForm($action, 'post');
        $html .= Html::hiddenInput('id', (string)$id);
        $html .= Html::hiddenInput('hash', $hash);

        foreach ((array)$params as $key => $value) {
            if (is_scalar($value)) {
                $html .= Html::hiddenInput('params[' . (string)$key . ']', (string)$value);
            }
        }

        $html .= Html::submitButton(Craft::t('formie', 'Cancel subscription'));
        $html .= '</form></body></html>';

        return $html;
    }
}
