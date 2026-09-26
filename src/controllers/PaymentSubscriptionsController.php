<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\base\Payment;
use verbb\formie\models\payments\CancelSubscriptionCommand;

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

    protected array|bool|int $allowAnonymous = ['cancel'];


    // Public Methods
    // =========================================================================

    public function actionCancel(): ?Response
    {
        $id = $this->request->getRequiredParam('id');
        $token = (string)$this->request->getRequiredParam('token');


        $subscription = Formie::$plugin->getSubscriptions()->getSubscriptionById($id);

        if (!$subscription) {
            return $this->asFailure(Craft::t('formie', 'Subscription not found.'));
        }

        $command = new CancelSubscriptionCommand((int)$id, $token);
        $command->authorize($subscription);
        if (!$this->request->getIsPost()) {
            return $this->asRaw($this->_renderCancelConfirmation((int)$id, $token));
        }
        $result = Formie::$plugin->getSubscriptions()->cancelAuthorized($subscription, $command);

        if (!$result) {
            return $this->asFailure(Craft::t('formie', 'Unable to cancel subscription.'));
        }

        return $this->asSuccess(Craft::t('formie', 'Subscription cancellation requested.'), data: [
            'subscriptionId' => $subscription->id,
            'status' => $subscription->status,
        ]);
    }

    private function _renderCancelConfirmation(int $id, string $token): string
    {
        $action = Craft::$app->getUrlManager()->createUrl('actions/formie/payment-subscriptions/cancel');
        $html = '<!doctype html><html><head><meta charset="utf-8"><title>' . Html::encode(Craft::t('formie', 'Cancel subscription')) . '</title></head><body>';
        $html .= '<h1>' . Html::encode(Craft::t('formie', 'Cancel subscription')) . '</h1>';
        $html .= '<p>' . Html::encode(Craft::t('formie', 'Are you sure you want to cancel this subscription?')) . '</p>';
        $html .= Html::beginForm($action, 'post');
        $html .= Html::hiddenInput('id', (string)$id);
        $html .= Html::hiddenInput('token', $token);

        $html .= Html::submitButton(Craft::t('formie', 'Cancel subscription'));
        $html .= '</form></body></html>';

        return $html;
    }
}
