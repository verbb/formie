<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\enums\SubscriptionCancellationMode;
use verbb\formie\models\payments\CancelSubscriptionCommand;

use Craft;
use craft\helpers\Html;
use craft\web\Controller;

use yii\web\BadRequestHttpException;
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
        $id = (int)$this->request->getRequiredParam('id');
        $token = (string)$this->request->getRequiredParam('token');


        $subscription = Formie::$plugin->getSubscriptions()->getSubscriptionById($id);

        if (!$subscription) {
            return $this->asFailure(Craft::t('formie', 'Subscription not found.'));
        }

        $modeParam = $this->request->getParam('mode');
        $mode = $modeParam === null || $modeParam === ''
            ? null
            : SubscriptionCancellationMode::tryFrom((string)$modeParam);

        if ($modeParam !== null && $modeParam !== '' && $mode === null) {
            throw new BadRequestHttpException('Invalid cancellation mode.');
        }
        $command = new CancelSubscriptionCommand($id, $token, $mode);
        $command->authorize($subscription);
        $mode = $command->resolveMode($subscription);

        if (!$this->request->getIsPost()) {
            return $this->asRaw($this->_renderCancelConfirmation($id, $token, $mode));
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


    // Private Methods
    // =========================================================================

    private function _renderCancelConfirmation(int $id, string $token, ?SubscriptionCancellationMode $mode): string
    {
        $action = Craft::$app->getUrlManager()->createUrl('actions/formie/payment-subscriptions/cancel');
        $message = $mode === SubscriptionCancellationMode::AT_PERIOD_END
            ? Craft::t('formie', 'The subscription will remain active until the end of the current billing period. Do you want to continue?')
            : Craft::t('formie', 'The subscription will be cancelled immediately. Do you want to continue?');
        $html = '<!doctype html><html><head><meta charset="utf-8"><title>' . Html::encode(Craft::t('formie', 'Cancel subscription')) . '</title></head><body>';
        $html .= '<h1>' . Html::encode(Craft::t('formie', 'Cancel subscription')) . '</h1>';
        $html .= '<p>' . Html::encode($message) . '</p>';
        $html .= Html::beginForm($action, 'post');
        $html .= Html::hiddenInput('id', (string)$id);
        $html .= Html::hiddenInput('token', $token);

        if ($mode) {
            $html .= Html::hiddenInput('mode', $mode->value);
        }

        $html .= Html::submitButton(Craft::t('formie', 'Cancel subscription'));
        $html .= '</form></body></html>';

        return $html;
    }
}
